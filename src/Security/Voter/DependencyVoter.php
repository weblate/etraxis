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

use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Dependency" entities.
 */
class DependencyVoter extends Voter implements VoterInterface
{
    use PermissionsTrait;

    public const ADD_DEPENDENCY    = 'ADD_DEPENDENCY';
    public const REMOVE_DEPENDENCY = 'REMOVE_DEPENDENCY';

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
            self::ADD_DEPENDENCY    => Issue::class,
            self::REMOVE_DEPENDENCY => Issue::class,
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
            self::ADD_DEPENDENCY    => $this->isAddDependencyGranted($subject, $user),
            self::REMOVE_DEPENDENCY => $this->isRemoveDependencyGranted($subject, $user),
            default                 => false,
        };
    }

    /**
     * Whether a dependency can be added to the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isAddDependencyGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or closed.
        if ($subject->isSuspended() || $subject->isClosed()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::ManageDependencies);
    }

    /**
     * Whether a dependency can be removed from the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isRemoveDependencyGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or closed.
        if ($subject->isSuspended() || $subject->isClosed()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::ManageDependencies);
    }
}
