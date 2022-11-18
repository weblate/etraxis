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
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\FieldStrategy\DurationStrategy;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\StringValue;
use App\Entity\TextValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        private readonly TranslatorInterface $translator,
        private readonly ValidatorInterface $validator
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
