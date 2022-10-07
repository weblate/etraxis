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

use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Template" entities.
 */
class TemplateVoter extends Voter implements VoterInterface
{
    public const CREATE_TEMPLATE          = 'CREATE_TEMPLATE';
    public const UPDATE_TEMPLATE          = 'UPDATE_TEMPLATE';
    public const DELETE_TEMPLATE          = 'DELETE_TEMPLATE';
    public const LOCK_TEMPLATE            = 'LOCK_TEMPLATE';
    public const UNLOCK_TEMPLATE          = 'UNLOCK_TEMPLATE';
    public const GET_TEMPLATE_PERMISSIONS = 'GET_TEMPLATE_PERMISSIONS';
    public const SET_TEMPLATE_PERMISSIONS = 'SET_TEMPLATE_PERMISSIONS';

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
            self::CREATE_TEMPLATE          => Project::class,
            self::UPDATE_TEMPLATE          => Template::class,
            self::DELETE_TEMPLATE          => Template::class,
            self::LOCK_TEMPLATE            => Template::class,
            self::UNLOCK_TEMPLATE          => Template::class,
            self::GET_TEMPLATE_PERMISSIONS => Template::class,
            self::SET_TEMPLATE_PERMISSIONS => Template::class,
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
            self::CREATE_TEMPLATE          => $this->isCreateGranted($subject, $user),
            self::UPDATE_TEMPLATE          => $this->isUpdateGranted($subject, $user),
            self::DELETE_TEMPLATE          => $this->isDeleteGranted($subject, $user),
            self::LOCK_TEMPLATE            => $this->isLockGranted($subject, $user),
            self::UNLOCK_TEMPLATE          => $this->isUnlockGranted($subject, $user),
            self::GET_TEMPLATE_PERMISSIONS => $this->isGetPermissionsGranted($subject, $user),
            self::SET_TEMPLATE_PERMISSIONS => $this->isSetPermissionsGranted($subject, $user),
            default                        => false,
        };
    }

    /**
     * Whether a new template can be created in the specified project.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Project $subject Subject project
     * @param User    $user    Current user
     */
    protected function isCreateGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified template can be updated.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isUpdateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified template can be deleted.
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(Template $subject, User $user): bool
    {
        // User must be an admin.
        if (!$user->isAdmin()) {
            return false;
        }

        // Can't delete a template if at least one issue is created using it.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(issue.id)')
            ->from(Issue::class, 'issue')
            ->innerJoin('issue.state', 'state')
            ->where('state.template = :template')
            ->setParameter('template', $subject->getId())
        ;

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $result;
    }

    /**
     * Whether the specified template can be locked.
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isLockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin() && !$subject->isLocked();
    }

    /**
     * Whether the specified template can be unlocked.
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isUnlockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->isLocked();
    }

    /**
     * Whether permissions of the specified template can be retrieved.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isGetPermissionsGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether permissions of the specified template can be changed.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isSetPermissionsGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin();
    }
}
