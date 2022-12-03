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

namespace App\MessageHandler\Issues;

use App\Entity\Comment;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Issues\GetIssuesQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetIssuesQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(GetIssuesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_USER)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $dql = $this->repository->createQueryBuilder('issue');
        $dql->distinct();

        // Include states.
        $dql->innerJoin('issue.state', 'state');
        $dql->addSelect('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Include author.
        $dql->innerJoin('issue.author', 'author');
        $dql->addSelect('author');

        // Include responsible.
        $dql->leftJoin('issue.responsible', 'responsible');
        $dql->addSelect('responsible');

        // Retrieve only issues the user is allowed to see.
        $dql
            ->leftJoin('template.rolePermissions', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissions', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->andWhere($dql->expr()->orX(
                'issue.author = :user',
                'issue.responsible = :user',
                'trp.role = :role',
                $dql->expr()->in('tgp.group', ':groups')
            ))
            ->setParameters([
                'permission' => TemplatePermissionEnum::ViewIssues,
                'role'       => SystemRoleEnum::Anyone,
                'user'       => $user,
                'groups'     => $user->getGroups(),
            ])
        ;

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = match ($property) {
                GetIssuesQuery::ISSUE_ID               => $this->queryFilterByFullId($dql, $value),
                GetIssuesQuery::ISSUE_SUBJECT          => $this->queryFilterBySubject($dql, $value),
                GetIssuesQuery::ISSUE_PROJECT          => $this->queryFilterByProjectId($dql, $value),
                GetIssuesQuery::ISSUE_PROJECT_NAME     => $this->queryFilterByProjectName($dql, $value),
                GetIssuesQuery::ISSUE_TEMPLATE         => $this->queryFilterByTemplateId($dql, $value),
                GetIssuesQuery::ISSUE_TEMPLATE_NAME    => $this->queryFilterByTemplateName($dql, $value),
                GetIssuesQuery::ISSUE_STATE            => $this->queryFilterByStateId($dql, $value),
                GetIssuesQuery::ISSUE_STATE_NAME       => $this->queryFilterByStateName($dql, $value),
                GetIssuesQuery::ISSUE_AUTHOR           => $this->queryFilterByAuthorId($dql, $value),
                GetIssuesQuery::ISSUE_AUTHOR_NAME      => $this->queryFilterByAuthorName($dql, $value),
                GetIssuesQuery::ISSUE_RESPONSIBLE      => $this->queryFilterByResponsibleId($dql, $value),
                GetIssuesQuery::ISSUE_RESPONSIBLE_NAME => $this->queryFilterByResponsibleName($dql, $value),
                GetIssuesQuery::ISSUE_IS_CLONED        => $this->queryFilterByIsCloned($dql, $value),
                GetIssuesQuery::ISSUE_IS_CRITICAL      => $this->queryFilterByIsCritical($dql, $value),
                GetIssuesQuery::ISSUE_IS_SUSPENDED     => $this->queryFilterByIsSuspended($dql, $value),
                GetIssuesQuery::ISSUE_IS_CLOSED        => $this->queryFilterByIsClosed($dql, $value),
                GetIssuesQuery::ISSUE_AGE              => $this->queryFilterByAge($dql, $value),
                default                                => $dql,
            };
        }

        // Total number of entities.
        $count = clone $dql;
        $count->distinct();
        $count->select('issue.id');
        $total = count($count->getQuery()->execute());

        // Issues age.
        $dql->addSelect('CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400) AS age');
        $dql->setParameter('now', time());

        // Sorting.
        foreach ($query->getOrder() as $property => $direction) {
            $dql = $this->queryOrder($dql, $property, $direction);
        }

        // Pagination.
        if (0 !== $query->getLimit()) {
            $dql->setFirstResult($query->getOffset());
            $dql->setMaxResults($query->getLimit());
        }

        // Execute query.
        $items = array_map(fn (array $entry) => reset($entry), $dql->getQuery()->execute());

        return new Collection($total, $items);
    }

    /**
     * Alters query in accordance with the specified search.
     */
    private function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (0 !== mb_strlen($search ?? '')) {
            // Search in comments.
            $comments = $this->manager->createQueryBuilder()
                ->select('issue.id')
                ->from(Comment::class, 'comment')
                ->innerJoin('comment.event', 'event')
                ->innerJoin('event.issue', 'issue')
                ->where('LOWER(comment.body) LIKE LOWER(:search)')
                ->andWhere('comment.private = :private')
                ->setParameters([
                    'search'  => "%{$search}%",
                    'private' => false,
                ])
            ;

            $issues = array_map(fn (array $entry) => $entry['id'], $comments->getQuery()->execute());

            $dql->andWhere($dql->expr()->orX(
                'LOWER(issue.subject) LIKE :search',
                $dql->expr()->in('issue', ':comments')
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
            $dql->setParameter('comments', $issues);
        }

        return $dql;
    }

    /**
     * Alters query to filter by human-readable issue ID.
     */
    private function queryFilterByFullId(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            // Issues human-readable ID.
            $dql->andWhere("LOWER(CONCAT(template.prefix, '-', LPAD(CONCAT('', issue.id), GREATEST(3, LENGTH(CONCAT('', issue.id))), '0'))) LIKE LOWER(:full_id)");
            $dql->setParameter('full_id', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue subject.
     */
    private function queryFilterBySubject(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(issue.subject) LIKE LOWER(:subject)');
            $dql->setParameter('subject', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue's project.
     */
    private function queryFilterByProjectId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('template.project = :project');
        $dql->setParameter('project', $value);

        return $dql;
    }

    /**
     * Alters query to filter by project name.
     */
    private function queryFilterByProjectName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(project.name) LIKE LOWER(:project_name)');
            $dql->setParameter('project_name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue's template.
     */
    private function queryFilterByTemplateId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('state.template = :template');
        $dql->setParameter('template', $value);

        return $dql;
    }

    /**
     * Alters query to filter by template name.
     */
    private function queryFilterByTemplateName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(template.name) LIKE LOWER(:template_name)');
            $dql->setParameter('template_name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue's state.
     */
    private function queryFilterByStateId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('issue.state = :state');
        $dql->setParameter('state', $value);

        return $dql;
    }

    /**
     * Alters query to filter by state name.
     */
    private function queryFilterByStateName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(state.name) LIKE LOWER(:state_name)');
            $dql->setParameter('state_name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue's author.
     */
    private function queryFilterByAuthorId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('issue.author = :author');
        $dql->setParameter('author', $value);

        return $dql;
    }

    /**
     * Alters query to filter by author full name.
     */
    private function queryFilterByAuthorName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(author.fullname) LIKE LOWER(:author_name)');
            $dql->setParameter('author_name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by issue's responsible.
     */
    private function queryFilterByResponsibleId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        if (null === $value) {
            $dql->andWhere('issue.responsible IS NULL');
        } else {
            $dql->andWhere('issue.responsible = :responsible');
            $dql->setParameter('responsible', $value);
        }

        return $dql;
    }

    /**
     * Alters query to filter by responsible full name.
     */
    private function queryFilterByResponsibleName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(responsible.fullname) LIKE LOWER(:responsible_name)');
            $dql->setParameter('responsible_name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by cloned issues.
     */
    private function queryFilterByIsCloned(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere($value ? 'issue.origin IS NOT NULL' : 'issue.origin IS NULL');

        return $dql;
    }

    /**
     * Alters query to filter by critical issues.
     */
    private function queryFilterByIsCritical(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        if ($value) {
            $expr = $dql->expr()->andX(
                'template.criticalAge IS NOT NULL',
                'issue.closedAt IS NULL',
                'template.criticalAge < CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400)'
            );
        } else {
            $expr = $dql->expr()->orX(
                'template.criticalAge IS NULL',
                'issue.closedAt IS NOT NULL',
                'template.criticalAge >= CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400)'
            );
        }

        $dql->andWhere($expr);
        $dql->setParameter('now', time());

        return $dql;
    }

    /**
     * Alters query to filter by suspended issues.
     */
    private function queryFilterByIsSuspended(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        if ($value) {
            $expr = $dql->expr()->andX(
                'issue.resumesAt IS NOT NULL',
                'issue.resumesAt > :now'
            );
        } else {
            $expr = $dql->expr()->orX(
                'issue.resumesAt IS NULL',
                'issue.resumesAt <= :now'
            );
        }

        $dql->andWhere($expr);
        $dql->setParameter('now', time());

        return $dql;
    }

    /**
     * Alters query to filter by closed issues.
     */
    private function queryFilterByIsClosed(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere($value ? 'issue.closedAt IS NOT NULL' : 'issue.closedAt IS NULL');

        return $dql;
    }

    /**
     * Alters query to filter by issue's age.
     */
    private function queryFilterByAge(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        if (null !== $value) {
            $dql->andWhere('CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400) = :age');
            $dql->setParameter('age', $value);
            $dql->setParameter('now', time());
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetIssuesQuery::ISSUE_ID          => 'issue.id',
            GetIssuesQuery::ISSUE_SUBJECT     => 'issue.subject',
            GetIssuesQuery::ISSUE_PROJECT     => 'project.name',
            GetIssuesQuery::ISSUE_TEMPLATE    => 'template.name',
            GetIssuesQuery::ISSUE_STATE       => 'state.name',
            GetIssuesQuery::ISSUE_AUTHOR      => 'author.fullname',
            GetIssuesQuery::ISSUE_RESPONSIBLE => 'responsible.fullname',
            GetIssuesQuery::ISSUE_CREATED_AT  => 'issue.createdAt',
            GetIssuesQuery::ISSUE_CHANGED_AT  => 'issue.changedAt',
            GetIssuesQuery::ISSUE_CLOSED_AT   => 'issue.closedAt',
            GetIssuesQuery::ISSUE_AGE         => 'age',
            default                           => null,
        };

        if ($order) {
            $dql->addOrderBy($order, AbstractCollectionQuery::SORT_DESC === mb_strtoupper($direction ?? '')
                ? AbstractCollectionQuery::SORT_DESC
                : AbstractCollectionQuery::SORT_ASC);
        }

        return $dql;
    }
}
