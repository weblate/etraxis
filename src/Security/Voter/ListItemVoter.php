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

namespace App\Security\Voter;

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\ListItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "ListItem" entities.
 */
class ListItemVoter extends Voter implements VoterInterface
{
    public const CREATE_LISTITEM = 'CREATE_LISTITEM';
    public const UPDATE_LISTITEM = 'UPDATE_LISTITEM';
    public const DELETE_LISTITEM = 'DELETE_LISTITEM';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected EntityManagerInterface $manager)
    {
    }

    /**
     * @see Voter::supports
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::CREATE_LISTITEM => Field::class,
            self::UPDATE_LISTITEM => ListItem::class,
            self::DELETE_LISTITEM => ListItem::class,
        ];

        return array_key_exists($attribute, $attributes)
            && (null === $attributes[$attribute] || $subject instanceof $attributes[$attribute]);
    }

    /**
     * @see Voter::voteOnAttribute
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE_LISTITEM => $this->isCreateGranted($subject, $user),
            self::UPDATE_LISTITEM => $this->isUpdateGranted($subject, $user),
            self::DELETE_LISTITEM => $this->isDeleteGranted($subject, $user),
            default               => false,
        };
    }

    /**
     * Whether a new item can be created in the specified field.
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     */
    protected function isCreateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getState()->getTemplate()->isLocked() && FieldTypeEnum::List === $subject->getType();
    }

    /**
     * Whether the specified item can be updated.
     *
     * @param ListItem $subject Subject item
     * @param User     $user    Current user
     */
    protected function isUpdateGranted(ListItem $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getField()->getState()->getTemplate()->isLocked();
    }

    /**
     * Whether the specified item can be deleted.
     *
     * @param ListItem $subject Subject item
     * @param User     $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(ListItem $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        if (!$user->isAdmin() || !$subject->getField()->getState()->getTemplate()->isLocked()) {
            return false;
        }

        // Can't delete an item if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(fv.id)')
            ->from(FieldValue::class, 'fv')
            ->where('fv.field = :field')
            ->andWhere('fv.value = :value')
            ->setParameter('field', $subject->getField()->getId())
            ->setParameter('value', $subject->getId())
        ;

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $result;
    }
}
