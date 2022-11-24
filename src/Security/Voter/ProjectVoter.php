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
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "Project" entities.
 */
class ProjectVoter extends Voter implements VoterInterface
{
    public const CREATE_PROJECT  = 'CREATE_PROJECT';
    public const UPDATE_PROJECT  = 'UPDATE_PROJECT';
    public const DELETE_PROJECT  = 'DELETE_PROJECT';
    public const SUSPEND_PROJECT = 'SUSPEND_PROJECT';
    public const RESUME_PROJECT  = 'RESUME_PROJECT';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly EntityManagerInterface $manager)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::CREATE_PROJECT  => null,
            self::UPDATE_PROJECT  => Project::class,
            self::DELETE_PROJECT  => Project::class,
            self::SUSPEND_PROJECT => Project::class,
            self::RESUME_PROJECT  => Project::class,
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
            self::CREATE_PROJECT  => $this->isCreateGranted($user),
            self::UPDATE_PROJECT  => $this->isUpdateGranted($subject, $user),
            self::DELETE_PROJECT  => $this->isDeleteGranted($subject, $user),
            self::SUSPEND_PROJECT => $this->isSuspendGranted($subject, $user),
            self::RESUME_PROJECT  => $this->isResumeGranted($subject, $user),
            default               => false,
        };
    }

    /**
     * Whether the current user can create a new project.
     *
     * @param User $user Current user
     */
    protected function isCreateGranted(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified project can be updated.
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @param Project $subject Subject project
     * @param User    $user    Current user
     */
    protected function isUpdateGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the specified project can be deleted.
     *
     * @param Project $subject Subject project
     * @param User    $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(Project $subject, User $user): bool
    {
        // User must be an admin.
        if (!$user->isAdmin()) {
            return false;
        }

        // Can't delete a project if there is at least one issue there.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(issue.id)')
            ->from(Issue::class, 'issue')
            ->innerJoin('issue.state', 'state')
            ->innerJoin('state.template', 'template')
            ->where('template.project = :project')
            ->setParameter('project', $subject->getId())
        ;

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $result;
    }

    /**
     * Whether the specified project can be suspended.
     *
     * @param Project $subject Subject project
     * @param User    $user    Current user
     */
    protected function isSuspendGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin() && !$subject->isSuspended();
    }

    /**
     * Whether the specified project can be resumed.
     *
     * @param Project $subject Subject project
     * @param User    $user    Current user
     */
    protected function isResumeGranted(Project $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->isSuspended();
    }
}
