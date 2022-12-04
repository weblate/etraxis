<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Repository;

use App\Entity\DecimalValue;
use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\FieldStrategy\DurationStrategy;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\StringValue;
use App\Entity\TextValue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * 'FieldValue' entities repository.
 */
class FieldValueRepository extends ServiceEntityRepository implements Contracts\FieldValueRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        ManagerRegistry $registry,
        protected readonly TranslatorInterface $translator,
        protected readonly ValidatorInterface $validator
    ) {
        parent::__construct($registry, FieldValue::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(FieldValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(FieldValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByIssue(Issue $issue, User $user, FieldPermissionEnum $access = FieldPermissionEnum::ReadOnly): array
    {
        // Basic query.
        $query = $this->createQueryBuilder('value')
            ->addSelect('transition')
            ->addSelect('field')
            ->addSelect('event')
            ->addSelect('state')
            ->addSelect('issue')
            ->innerJoin('value.transition', 'transition')
            ->innerJoin('value.field', 'field')
            ->innerJoin('transition.event', 'event')
            ->innerJoin('transition.state', 'state')
            ->innerJoin('event.issue', 'issue')
            ->where('event.issue = :issue')
            ->addOrderBy('event.createdAt')
            ->addOrderBy('field.position')
        ;

        // Retrieve only fields the user is allowed to access.
        $query
            ->leftJoin('field.rolePermissions', 'frp_anyone', Join::WITH, 'frp_anyone.role = :role_anyone')
            ->leftJoin('field.rolePermissions', 'frp_author', Join::WITH, 'frp_author.role = :role_author')
            ->leftJoin('field.rolePermissions', 'frp_responsible', Join::WITH, 'frp_responsible.role = :role_responsible')
            ->leftJoin('field.groupPermissions', 'fgp')
            ->andWhere($query->expr()->orX(
                $query->expr()->in('frp_anyone.permission', ':permissions'),
                $query->expr()->andX(
                    'issue.author = :user',
                    $query->expr()->in('frp_author.permission', ':permissions')
                ),
                $query->expr()->andX(
                    'issue.responsible = :user',
                    $query->expr()->in('frp_responsible.permission', ':permissions')
                ),
                $query->expr()->andX(
                    $query->expr()->in('fgp.group', ':groups'),
                    $query->expr()->in('fgp.permission', ':permissions')
                )
            ))
        ;

        $query->setParameters([
            'issue'            => $issue,
            'user'             => $user,
            'groups'           => $user->getGroups(),
            'role_anyone'      => SystemRoleEnum::Anyone->value,
            'role_author'      => SystemRoleEnum::Author->value,
            'role_responsible' => SystemRoleEnum::Responsible->value,
            'permissions'      => match ($access) {
                FieldPermissionEnum::ReadOnly     => [FieldPermissionEnum::ReadOnly->value, FieldPermissionEnum::ReadAndWrite->value],
                FieldPermissionEnum::ReadAndWrite => [FieldPermissionEnum::ReadAndWrite->value],
            },
        ]);

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getLatestValues(Issue $issue, User $user): array
    {
        $fieldValues = $this->findAllByIssue($issue, $user, FieldPermissionEnum::ReadAndWrite);

        $transitions = [];

        foreach ($fieldValues as $fieldValue) {
            $transitions[$fieldValue->getTransition()->getState()->getId()] = $fieldValue->getTransition()->getId();
        }

        $fieldValues = array_filter(
            $fieldValues,
            fn (FieldValue $fieldValue) => in_array($fieldValue->getTransition()->getId(), $transitions, true)
        );

        return array_values($fieldValues);
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldValues(array $fields, array $values, array $context = []): ConstraintViolationListInterface
    {
        $defaults    = [];
        $constraints = [];

        $context['repository'] = $this->getEntityManager()->getRepository(ListItem::class);

        foreach ($fields as $field) {
            $defaults[$field->getId()]    = null;
            $constraints[$field->getId()] = $field->getStrategy()->getValueValidationConstraints($this->translator, $context);
        }

        return $this->validator->validate($values + $defaults, new Assert\Collection([
            'fields'             => $constraints,
            'allowExtraFields'   => false,
            'allowMissingFields' => false,
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldValue(FieldValue $fieldValue, null|bool|int|string|ListItem $value): bool
    {
        if (null !== $value) {
            switch ($fieldValue->getField()->getType()) {
                case FieldTypeEnum::Checkbox:
                    $value = $value ? 1 : 0;

                    break;

                case FieldTypeEnum::Date:
                    $timezone = timezone_open($fieldValue->getTransition()->getEvent()->getUser()->getTimezone()) ?: null;
                    $value    = date_create_from_format('Y-m-d', $value, $timezone)->getTimestamp();

                    break;

                case FieldTypeEnum::Decimal:
                    /** @var \App\Repository\Contracts\DecimalValueRepositoryInterface $repository */
                    $repository = $this->getEntityManager()->getRepository(DecimalValue::class);
                    $value      = $repository->get($value)->getId();

                    break;

                case FieldTypeEnum::Duration:
                    $value = DurationStrategy::hhmm2int($value);

                    break;

                case FieldTypeEnum::Issue:
                    /** @var \App\Repository\Contracts\IssueRepositoryInterface $repository */
                    $repository = $this->getEntityManager()->getRepository(Issue::class);

                    if (null === $repository->find($value)) {
                        return false;
                    }

                    break;

                case FieldTypeEnum::List:
                    /** @var \App\Repository\Contracts\ListItemRepositoryInterface $repository */
                    $repository = $this->getEntityManager()->getRepository(ListItem::class);
                    $item       = $repository->findOneByValue($fieldValue->getField(), $value);

                    if (null === $item) {
                        return false;
                    }

                    $value = $item->getId();

                    break;

                case FieldTypeEnum::Number:
                    break;

                case FieldTypeEnum::String:
                    /** @var \App\Repository\Contracts\StringValueRepositoryInterface $repository */
                    $repository = $this->getEntityManager()->getRepository(StringValue::class);
                    $value      = $repository->get($value)->getId();

                    break;

                case FieldTypeEnum::Text:
                    /** @var \App\Repository\Contracts\TextValueRepositoryInterface $repository */
                    $repository = $this->getEntityManager()->getRepository(TextValue::class);
                    $value      = $repository->get($value)->getId();

                    break;
            }
        }

        $fieldValue->setValue($value);

        return true;
    }
}
