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
            $dql = $this->queryFilter($dql, $property, $value);
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
        $dql->setFirstResult($query->getOffset());
        $dql->setMaxResults($query->getLimit());

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
     * Alters query to filter by the specified property.
     */
    private function queryFilter(QueryBuilder $dql, string $property, null|bool|int|string $value = null): QueryBuilder
    {
        switch ($property) {
            case GetIssuesQuery::ISSUE_ID:
                if (0 !== mb_strlen((string) $value)) {
                    // Issues human-readable ID.
                    $dql->andWhere("LOWER(CONCAT(template.prefix, '-', LPAD(CONCAT('', issue.id), GREATEST(3, LENGTH(CONCAT('', issue.id))), '0'))) LIKE LOWER(:full_id)");
                    $dql->setParameter('full_id', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_SUBJECT:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(issue.subject) LIKE LOWER(:subject)');
                    $dql->setParameter('subject', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_PROJECT:
                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case GetIssuesQuery::ISSUE_PROJECT_NAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(project.name) LIKE LOWER(:project_name)');
                    $dql->setParameter('project_name', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_TEMPLATE:
                $dql->andWhere('state.template = :template');
                $dql->setParameter('template', (int) $value);

                break;

            case GetIssuesQuery::ISSUE_TEMPLATE_NAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(template.name) LIKE LOWER(:template_name)');
                    $dql->setParameter('template_name', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_STATE:
                $dql->andWhere('issue.state = :state');
                $dql->setParameter('state', (int) $value);

                break;

            case GetIssuesQuery::ISSUE_STATE_NAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(state.name) LIKE LOWER(:state_name)');
                    $dql->setParameter('state_name', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_AUTHOR:
                $dql->andWhere('issue.author = :author');
                $dql->setParameter('author', (int) $value);

                break;

            case GetIssuesQuery::ISSUE_AUTHOR_NAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(author.fullname) LIKE LOWER(:author_name)');
                    $dql->setParameter('author_name', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_RESPONSIBLE:
                if (null === $value) {
                    $dql->andWhere('issue.responsible IS NULL');
                } else {
                    $dql->andWhere('issue.responsible = :responsible');
                    $dql->setParameter('responsible', (int) $value);
                }

                break;

            case GetIssuesQuery::ISSUE_RESPONSIBLE_NAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(responsible.fullname) LIKE LOWER(:responsible_name)');
                    $dql->setParameter('responsible_name', "%{$value}%");
                }

                break;

            case GetIssuesQuery::ISSUE_IS_CLONED:
                $dql->andWhere($value ? 'issue.origin IS NOT NULL' : 'issue.origin IS NULL');

                break;

            case GetIssuesQuery::ISSUE_IS_CRITICAL:
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

                break;

            case GetIssuesQuery::ISSUE_IS_SUSPENDED:
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

                break;

            case GetIssuesQuery::ISSUE_IS_CLOSED:
                $dql->andWhere($value ? 'issue.closedAt IS NOT NULL' : 'issue.closedAt IS NULL');

                break;

            case GetIssuesQuery::ISSUE_AGE:
                if (null !== $value) {
                    $dql->andWhere('CEIL(CAST(COALESCE(issue.closedAt, :now) - issue.createdAt AS DECIMAL) / 86400) = :age');
                    $dql->setParameter('age', (int) $value);
                    $dql->setParameter('now', time());
                }

                break;
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $map = [
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
        ];

        if (isset($map[$property])) {
            if (AbstractCollectionQuery::SORT_DESC === mb_strtoupper($direction ?? '')) {
                $dql->addOrderBy($map[$property], AbstractCollectionQuery::SORT_DESC);
            } else {
                $dql->addOrderBy($map[$property], AbstractCollectionQuery::SORT_ASC);
            }
        }

        return $dql;
    }
}
