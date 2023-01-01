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

namespace App\MessageHandler\Projects;

use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Projects\GetProjectsQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetProjectsQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ProjectRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetProjectsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('project');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = match ($property) {
                GetProjectsQuery::PROJECT_NAME         => $this->queryFilterByName($dql, $value),
                GetProjectsQuery::PROJECT_DESCRIPTION  => $this->queryFilterByDescription($dql, $value),
                GetProjectsQuery::PROJECT_IS_SUSPENDED => $this->queryFilterByIsSuspended($dql, $value),
                default                                => $dql,
            };
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(project.id)');
        $total = (int) $count->getQuery()->getSingleScalarResult();

        // Sorting.
        foreach ($query->getOrder() as $property => $direction) {
            $dql = $this->queryOrder($dql, $property, $direction);
        }

        // Pagination.
        $dql->setFirstResult($query->getOffset());
        $dql->setMaxResults($query->getLimit());

        // Execute query.
        $items = $dql->getQuery()->getResult();

        return new Collection($total, $items);
    }

    /**
     * Alters query in accordance with the specified search.
     */
    private function querySearch(QueryBuilder $dql, ?string $search): QueryBuilder
    {
        if (0 !== mb_strlen($search ?? '')) {
            $dql->andWhere($dql->expr()->orX(
                'LOWER(project.name) LIKE :search',
                'LOWER(project.description) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by project name.
     */
    private function queryFilterByName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('project.name IS NULL');
        } else {
            $dql->andWhere('LOWER(project.name) LIKE LOWER(:name)');
            $dql->setParameter('name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by project description.
     */
    private function queryFilterByDescription(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('project.description IS NULL');
        } else {
            $dql->andWhere('LOWER(project.description) LIKE LOWER(:description)');
            $dql->setParameter('description', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by project status.
     */
    private function queryFilterByIsSuspended(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere('project.suspended = :suspended');
        $dql->setParameter('suspended', (bool) $value);

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetProjectsQuery::PROJECT_ID           => 'project.id',
            GetProjectsQuery::PROJECT_NAME         => 'project.name',
            GetProjectsQuery::PROJECT_DESCRIPTION  => 'project.description',
            GetProjectsQuery::PROJECT_CREATED_AT   => 'project.createdAt',
            GetProjectsQuery::PROJECT_IS_SUSPENDED => 'project.suspended',
            default                                => null,
        };

        if ($order) {
            $dql->addOrderBy($order, AbstractCollectionQuery::SORT_DESC === mb_strtoupper($direction ?? '')
                ? AbstractCollectionQuery::SORT_DESC
                : AbstractCollectionQuery::SORT_ASC);
        }

        return $dql;
    }
}
