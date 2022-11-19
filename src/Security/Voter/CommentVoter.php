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
 * Voter for "Comment" entities.
 */
class CommentVoter extends Voter implements VoterInterface
{
    use PermissionsTrait;

    public const ADD_PUBLIC_COMMENT   = 'ADD_PUBLIC_COMMENT';
    public const ADD_PRIVATE_COMMENT  = 'ADD_PRIVATE_COMMENT';
    public const READ_PRIVATE_COMMENT = 'READ_PRIVATE_COMMENT';

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
            self::ADD_PUBLIC_COMMENT   => Issue::class,
            self::ADD_PRIVATE_COMMENT  => Issue::class,
            self::READ_PRIVATE_COMMENT => Issue::class,
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
            self::ADD_PUBLIC_COMMENT   => $this->isAddPublicCommentGranted($subject, $user),
            self::ADD_PRIVATE_COMMENT  => $this->isAddPrivateCommentGranted($subject, $user),
            self::READ_PRIVATE_COMMENT => $this->isReadPrivateCommentGranted($subject, $user),
            default                    => false,
        };
    }

    /**
     * Whether a public comment can be added to the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isAddPublicCommentGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or frozen.
        if ($subject->isSuspended() || $subject->isFrozen()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::AddComments);
    }

    /**
     * Whether a private comment can be added to the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isAddPrivateCommentGranted(Issue $subject, User $user): bool
    {
        return $this->isAddPublicCommentGranted($subject, $user)
            && $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::PrivateComments);
    }

    /**
     * Whether user can read private comments of the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isReadPrivateCommentGranted(Issue $subject, User $user): bool
    {
        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::PrivateComments);
    }
}
