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

use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Group" entities.
 */
class GroupVoter extends Voter implements VoterInterface
{
    public const CREATE_GROUP         = 'CREATE_GROUP';
    public const UPDATE_GROUP         = 'UPDATE_GROUP';
    public const DELETE_GROUP         = 'DELETE_GROUP';
    public const MANAGE_GROUP_MEMBERS = 'MANAGE_GROUP_MEMBERS';

    /**
     * @see Voter::supports
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::CREATE_GROUP         => null,
            self::UPDATE_GROUP         => Group::class,
            self::DELETE_GROUP         => Group::class,
            self::MANAGE_GROUP_MEMBERS => Group::class,
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
            self::CREATE_GROUP         => $this->isCreateGranted($user),
            self::UPDATE_GROUP         => $this->isUpdateGranted($subject, $user),
            self::DELETE_GROUP         => $this->isDeleteGranted($subject, $user),
            self::MANAGE_GROUP_MEMBERS => $this->isManageMembershipGranted($subject, $user),
            default                    => false,
        };
    }

    /**
     * Whether the current user can create a new group.
     *
     * @param User $user Current user
     */
    protected function isCreateGranted(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified group can be updated.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Group $subject Subject group
     * @param User  $user    Current user
     */
    protected function isUpdateGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified group can be deleted.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Group $subject Subject group
     * @param User  $user    Current user
     */
    protected function isDeleteGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether list of members of the specified group can be managed.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Group $subject Subject group
     * @param User  $user    Current user
     */
    protected function isManageMembershipGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin();
    }
}
