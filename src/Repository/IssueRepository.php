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

namespace App\Repository;

use App\Entity\Dependency;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Event;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\StateGroupTransition;
use App\Entity\StateResponsibleGroup;
use App\Entity\StateRoleTransition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'Issue' entities repository.
 */
class IssueRepository extends ServiceEntityRepository implements Contracts\IssueRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Issue::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(Issue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(Issue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function refresh(Issue $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function hasOpenedDependencies(Issue $issue): bool
    {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('COUNT(dependency)')
            ->from(Dependency::class, 'dependency')
            ->innerJoin('dependency.event', 'event')
            ->innerJoin('dependency.issue', 'issue')
            ->where('event.issue = :issue')
            ->andWhere('issue.closedAt IS NULL')
            ->setParameter('issue', $issue)
        ;

        $count = (int) $query->getQuery()->getSingleScalarResult();

        return 0 !== $count;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransitionsByUser(Issue $issue, User $user): array
    {
        // List opened dependencies of the issue.
        $hasDependencies = $this->hasOpenedDependencies($issue);

        // List user's roles.
        $roles = [
            SystemRoleEnum::Anyone->value      => true,
            SystemRoleEnum::Author->value      => $user === $issue->getAuthor(),
            SystemRoleEnum::Responsible->value => $user === $issue->getResponsible(),
        ];

        $roles = array_filter($roles, fn (bool $role) => $role);
        $roles = array_keys($roles);

        // Check whether the user has required permissions by role.
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('st')
            ->from(StateRoleTransition::class, 'st')
            ->innerJoin('st.toState', 'toState')
            ->addSelect('toState')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.role', ':roles'))
            ->setParameters([
                'from'  => $issue->getState(),
                'roles' => $roles,
            ])
        ;

        if ($hasDependencies) {
            $query
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateTypeEnum::Final)
            ;
        }

        $statesByRole = array_map(fn (StateRoleTransition $transition) => $transition->getToState(), $query->getQuery()->getResult());

        // Check whether the user has required permissions by group.
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('st')
            ->from(StateGroupTransition::class, 'st')
            ->innerJoin('st.toState', 'toState')
            ->addSelect('toState')
            ->where('st.fromState = :from')
            ->andWhere($query->expr()->in('st.group', ':groups'))
            ->setParameters([
                'from'   => $issue->getState(),
                'groups' => $user->getGroups(),
            ])
        ;

        if ($hasDependencies) {
            $query
                ->andWhere('toState.type != :type')
                ->setParameter('type', StateTypeEnum::Final)
            ;
        }

        $statesByGroup = array_map(fn (StateGroupTransition $transition) => $transition->getToState(), $query->getQuery()->getResult());

        $states = array_merge($statesByRole, $statesByGroup);
        $states = array_unique($states, SORT_REGULAR);

        usort($states, fn (State $state1, State $state2) => strcmp($state1->getName(), $state2->getName()));

        return $states;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponsiblesByState(State $state): array
    {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query
            ->select('user')
            ->from(User::class, 'user')
            ->from(StateResponsibleGroup::class, 'sr')
            ->innerJoin('user.groups', 'grp')
            ->where('sr.group = grp')
            ->andWhere('sr.state = :state')
            ->orderBy('user.fullname')
            ->setParameter('state', $state)
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function reduceByUser(User $user, array $issues): array
    {
        $query = $this->createQueryBuilder('issue');

        $query
            ->innerJoin('issue.state', 'state')
            ->addSelect('state')
            ->innerJoin('state.template', 'template')
            ->addSelect('template')
            ->innerJoin('template.project', 'project')
            ->addSelect('project')
            ->innerJoin('issue.author', 'author')
            ->addSelect('author')
            ->leftJoin('issue.responsible', 'responsible')
            ->addSelect('responsible')
            ->leftJoin('template.rolePermissions', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissions', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->where($query->expr()->in('issue.id', ':issues'))
            ->andWhere($query->expr()->orX(
                'issue.author = :user',
                'issue.responsible = :user',
                'trp.role = :role',
                $query->expr()->in('tgp.group', ':groups')
            ))
            ->orderBy('issue.id')
            ->setParameters([
                'permission' => TemplatePermissionEnum::ViewIssues->value,
                'role'       => SystemRoleEnum::Anyone->value,
                'user'       => $user,
                'groups'     => $user->getGroups(),
                'issues'     => $issues,
            ])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function assignIssue(User $user, Issue $issue, User $responsible): bool
    {
        $responsibles = $this->getResponsiblesByState($issue->getState());

        if (!in_array($responsible, $responsibles, true)) {
            return false;
        }

        $event = new Event($issue, $user, EventTypeEnum::IssueAssigned, $responsible->getFullname());

        $issue->getEvents()->add($event);
        $issue->setResponsible($responsible);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function reassignIssue(User $user, Issue $issue, User $responsible): bool
    {
        if (!$issue->getResponsible()) {
            return false;
        }

        $responsibles = $this->getResponsiblesByState($issue->getState());

        if (!in_array($responsible, $responsibles, true)) {
            return false;
        }

        $event = new Event($issue, $user, EventTypeEnum::IssueReassigned, $responsible->getFullname());

        $issue->getEvents()->add($event);
        $issue->setResponsible($responsible);

        return true;
    }
}
