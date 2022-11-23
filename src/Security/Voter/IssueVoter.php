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

use App\Entity\Enums\StateTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Issue;
use App\Entity\StateGroupTransition;
use App\Entity\StateRoleTransition;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Issue" entities.
 */
class IssueVoter extends Voter implements VoterInterface
{
    use PermissionsTrait;

    public const VIEW_ISSUE     = 'VIEW_ISSUE';
    public const CREATE_ISSUE   = 'CREATE_ISSUE';
    public const UPDATE_ISSUE   = 'UPDATE_ISSUE';
    public const DELETE_ISSUE   = 'DELETE_ISSUE';
    public const CHANGE_STATE   = 'CHANGE_STATE';
    public const REASSIGN_ISSUE = 'REASSIGN_ISSUE';
    public const SUSPEND_ISSUE  = 'SUSPEND_ISSUE';
    public const RESUME_ISSUE   = 'RESUME_ISSUE';

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
            self::VIEW_ISSUE     => Issue::class,
            self::CREATE_ISSUE   => Template::class,
            self::UPDATE_ISSUE   => Issue::class,
            self::DELETE_ISSUE   => Issue::class,
            self::CHANGE_STATE   => Issue::class,
            self::REASSIGN_ISSUE => Issue::class,
            self::SUSPEND_ISSUE  => Issue::class,
            self::RESUME_ISSUE   => Issue::class,
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
            self::VIEW_ISSUE     => $this->isViewGranted($subject, $user),
            self::CREATE_ISSUE   => $this->isCreateGranted($subject, $user),
            self::UPDATE_ISSUE   => $this->isUpdateGranted($subject, $user),
            self::DELETE_ISSUE   => $this->isDeleteGranted($subject, $user),
            self::CHANGE_STATE   => $this->isChangeStateGranted($subject, $user),
            self::REASSIGN_ISSUE => $this->isReassignGranted($subject, $user),
            self::SUSPEND_ISSUE  => $this->isSuspendGranted($subject, $user),
            self::RESUME_ISSUE   => $this->isResumeGranted($subject, $user),
            default              => false,
        };
    }

    /**
     * Whether the specified issue can be viewed.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isViewGranted(Issue $subject, User $user): bool
    {
        // Authors can always view their issues.
        if ($subject->getAuthor() === $user) {
            return true;
        }

        // Responsibles can always view their issues.
        if ($subject->getResponsible() === $user) {
            return true;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::ViewIssues);
    }

    /**
     * Whether a new issue can be created using the specified template.
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isCreateGranted(Template $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // One of the states must be set as initial.
        if (null === $subject->getInitialState()) {
            return false;
        }

        return $this->hasRolePermission($this->manager, $subject, SystemRoleEnum::Anyone, TemplatePermissionEnum::CreateIssues)
            || $this->hasGroupPermission($this->manager, $subject, $user, TemplatePermissionEnum::CreateIssues);
    }

    /**
     * Whether the specified issue can be updated.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isUpdateGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or frozen.
        if ($subject->isSuspended() || $subject->isFrozen()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::EditIssues);
    }

    /**
     * Whether the specified issue can be deleted.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isDeleteGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended.
        if ($subject->isSuspended()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::DeleteIssues);
    }

    /**
     * Whether the current state of the specified issue can be changed to the specified state.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isChangeStateGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or frozen.
        if ($subject->isSuspended() || $subject->isFrozen()) {
            return false;
        }

        /** @var \App\Repository\Contracts\IssueRepositoryInterface $repository */
        $repository = $this->manager->getRepository(Issue::class);

        // Check whether the issue has opened dependencies.
        $hasDependencies = $repository->hasOpenedDependencies($subject);

        // Check whether the user has required permissions by role.
        $roles = [SystemRoleEnum::Anyone];

        if ($subject->getAuthor() === $user) {
            $roles[] = SystemRoleEnum::Author;
        }

        if ($subject->getResponsible() === $user) {
            $roles[] = SystemRoleEnum::Responsible;
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(st.role)')
            ->from(StateRoleTransition::class, 'st')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.role', ':roles'))
            ->setParameters([
                'from'  => $subject->getState(),
                'roles' => $roles,
            ])
        ;

        if ($hasDependencies) {
            $query
                ->innerJoin('st.toState', 'toState')
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateTypeEnum::Final)
            ;
        }

        $result = (int) $query->getQuery()->getSingleScalarResult();

        if (0 !== $result) {
            return true;
        }

        // Check whether the user has required permissions by group.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(st.group)')
            ->from(StateGroupTransition::class, 'st')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.group', ':groups'))
            ->setParameters([
                'from'   => $subject->getState(),
                'groups' => $user->getGroups(),
            ])
        ;

        if ($hasDependencies) {
            $query
                ->innerJoin('st.toState', 'toState')
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateTypeEnum::Final)
            ;
        }

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 !== $result;
    }

    /**
     * Whether the specified user can reassign specified issue.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isReassignGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or closed.
        if ($subject->isSuspended() || $subject->isClosed()) {
            return false;
        }

        // Issue must be assigned.
        if (null === $subject->getResponsible()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::ReassignIssues);
    }

    /**
     * Whether the specified issue can be suspended.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isSuspendGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or closed.
        if ($subject->isSuspended() || $subject->isClosed()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::SuspendIssues);
    }

    /**
     * Whether the specified issue can be resumed.
     *
     * @param Issue $subject Subject issue
     * @param User  $user    Current user
     */
    protected function isResumeGranted(Issue $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->getTemplate()->isLocked() || $subject->getProject()->isSuspended()) {
            return false;
        }

        // Issue must not be suspended or closed.
        if (!$subject->isSuspended() || $subject->isClosed()) {
            return false;
        }

        return $this->hasPermission($this->manager, $subject, $user, TemplatePermissionEnum::ResumeIssues);
    }
}
