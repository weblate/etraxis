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

use App\Entity\Event;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "User" entities.
 */
class UserVoter extends Voter
{
    public const CREATE_USER        = 'CREATE_USER';
    public const UPDATE_USER        = 'UPDATE_USER';
    public const DELETE_USER        = 'DELETE_USER';
    public const DISABLE_USER       = 'DISABLE_USER';
    public const ENABLE_USER        = 'ENABLE_USER';
    public const SET_PASSWORD       = 'SET_USER_PASSWORD';
    public const MANAGE_USER_GROUPS = 'MANAGE_USER_GROUPS';

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
            self::CREATE_USER        => null,
            self::UPDATE_USER        => User::class,
            self::DELETE_USER        => User::class,
            self::DISABLE_USER       => User::class,
            self::ENABLE_USER        => User::class,
            self::SET_PASSWORD       => User::class,
            self::MANAGE_USER_GROUPS => User::class,
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
            self::CREATE_USER        => $this->isCreateGranted($user),
            self::UPDATE_USER        => $this->isUpdateGranted($subject, $user),
            self::DELETE_USER        => $this->isDeleteGranted($subject, $user),
            self::DISABLE_USER       => $this->isDisableGranted($subject, $user),
            self::ENABLE_USER        => $this->isEnableGranted($subject, $user),
            self::SET_PASSWORD       => $this->isSetPasswordGranted($subject, $user),
            self::MANAGE_USER_GROUPS => $this->isManageMembershipGranted($subject, $user),
            default                  => false,
        };
    }

    /**
     * Whether the current user can create a new one.
     *
     * @param User $user Current user
     */
    protected function isCreateGranted(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified user can be updated.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param User $subject Subject user
     * @param User $user    Current user
     */
    protected function isUpdateGranted(User $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified user can be deleted.
     *
     * @param User $subject Subject user
     * @param User $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(User $subject, User $user): bool
    {
        // User must be an admin and cannot delete oneself.
        if (!$user->isAdmin() || $subject->getId() === $user->getId()) {
            return false;
        }

        // Can't delete a user if mentioned in an issue history.
        $query = $this->manager->createQueryBuilder()
            ->select('COUNT(event.id)')
            ->from(Event::class, 'event')
            ->where('event.user = :user')
            ->setParameter('user', $subject->getId())
        ;

        $events = (int) $query->getQuery()->getSingleScalarResult();

        // Can't delete a user if they created an existing issue or are assigned to it.
        $query = $this->manager->createQueryBuilder()
            ->select('COUNT(issue.id)')
            ->from(Issue::class, 'issue')
            ->where('issue.author = :user')
            ->orWhere('issue.responsible = :user')
            ->setParameter('user', $subject->getId())
        ;

        $issues = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $events && 0 === $issues;
    }

    /**
     * Whether the specified user can be disabled.
     *
     * @param User $subject Subject user
     * @param User $user    Current user
     */
    protected function isDisableGranted(User $subject, User $user): bool
    {
        // Can't disable oneself.
        if ($subject->getId() === $user->getId()) {
            return false;
        }

        return $user->isAdmin() && !$subject->isDisabled();
    }

    /**
     * Whether the specified user can be enabled.
     *
     * @param User $subject Subject user
     * @param User $user    Current user
     */
    protected function isEnableGranted(User $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->isDisabled();
    }

    /**
     * Whether a password of the specified user can be set.
     *
     * @param User $subject Subject user
     * @param User $user    Current user
     */
    protected function isSetPasswordGranted(User $subject, User $user): bool
    {
        // Can't set password of an external account.
        if ($subject->isAccountExternal()) {
            return false;
        }

        return $user->isAdmin() || $subject->getId() === $user->getId();
    }

    /**
     * Whether list of groups of the specified user can be managed.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param User $subject Subject User
     * @param User $user    Current user
     */
    protected function isManageMembershipGranted(User $subject, User $user): bool
    {
        return $user->isAdmin();
    }
}
