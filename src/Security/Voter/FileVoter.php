<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
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
 * Voter for "File" entities.
 */
class FileVoter extends Voter implements VoterInterface
{
    use PermissionsTrait;

    public const ATTACH_FILE = 'ATTACH_FILE';
    public const DELETE_FILE = 'DELETE_FILE';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly EntityManagerInterface $manager, protected readonly int $maxsize)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::ATTACH_FILE => Issue::class,
            self::DELETE_FILE => Issue::class,
        ];

        return array_key_exists($attribute, $attributes)
            && (null === $attributes[$attribute] || $subject instanceof $attributes[$attribute]);
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::ATTACH_FILE => $this->isAttachFileGranted($subject, $user),
            self::DELETE_FILE => $this->isDeleteFileGranted($subject, $user),
            default           => false,
        };
    }

    /**
     * Whether a file can be attached to the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isAttachFileGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Attachments should not be disabled.
        if (0 === $this->maxsize) {
            return false;
        }

        // Issue must not be suspended or frozen.
        if ($subject->isSuspended() || $subject->isFrozen()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::AttachFiles);
    }

    /**
     * Whether a file can be deleted from the specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isDeleteFileGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or frozen.
        if ($subject->isSuspended() || $subject->isFrozen()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::DeleteFiles);
    }
}
