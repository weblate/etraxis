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

use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\State;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Field" entities.
 */
class FieldVoter extends Voter implements VoterInterface
{
    public const CREATE_FIELD          = 'CREATE_FIELD';
    public const UPDATE_FIELD          = 'UPDATE_FIELD';
    public const REMOVE_FIELD          = 'REMOVE_FIELD';
    public const DELETE_FIELD          = 'DELETE_FIELD';
    public const GET_FIELD_PERMISSIONS = 'GET_FIELD_PERMISSIONS';
    public const SET_FIELD_PERMISSIONS = 'SET_FIELD_PERMISSIONS';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @see Voter::supports
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::CREATE_FIELD          => State::class,
            self::UPDATE_FIELD          => Field::class,
            self::REMOVE_FIELD          => Field::class,
            self::DELETE_FIELD          => Field::class,
            self::GET_FIELD_PERMISSIONS => Field::class,
            self::SET_FIELD_PERMISSIONS => Field::class,
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
            self::CREATE_FIELD          => $this->isCreateGranted($subject, $user),
            self::UPDATE_FIELD          => $this->isUpdateGranted($subject, $user),
            self::REMOVE_FIELD          => $this->isRemoveGranted($subject, $user),
            self::DELETE_FIELD          => $this->isDeleteGranted($subject, $user),
            self::GET_FIELD_PERMISSIONS => $this->isGetPermissionsGranted($subject, $user),
            self::SET_FIELD_PERMISSIONS => $this->isSetPermissionsGranted($subject, $user),
            default                     => false,
        };
    }

    /**
     * Whether a new field can be created in the specified state.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isCreateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getTemplate()->isLocked();
    }

    /**
     * Whether the specified field can be updated.
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     */
    protected function isUpdateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getState()->getTemplate()->isLocked();
    }

    /**
     * Whether the specified field can be removed (soft-deleted).
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     */
    protected function isRemoveGranted(Field $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        return $user->isAdmin() && $subject->getState()->getTemplate()->isLocked();
    }

    /**
     * Whether the specified field can be deleted.
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(Field $subject, User $user): bool
    {
        // It must be allowed to soft-delete the field.
        if (!$this->isRemoveGranted($subject, $user)) {
            return false;
        }

        // Can't delete a field if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(fv.id)')
            ->from(FieldValue::class, 'fv')
            ->where('fv.field = :field')
            ->setParameter('field', $subject->getId())
        ;

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $result;
    }

    /**
     * Whether permissions of the specified field can be retrieved.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     */
    protected function isGetPermissionsGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether permissions of the specified field can be changed.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Field $subject Subject field
     * @param User  $user    Current user
     */
    protected function isSetPermissionsGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin();
    }
}
