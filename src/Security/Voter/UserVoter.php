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

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "User" entities.
 */
class UserVoter extends Voter
{
    public const SET_PASSWORD = 'SET_USER_PASSWORD';

    /**
     * @see Voter::supports
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::SET_PASSWORD => User::class,
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
            self::SET_PASSWORD => $this->isSetPasswordGranted($subject, $user),
            default            => false,
        };
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
}
