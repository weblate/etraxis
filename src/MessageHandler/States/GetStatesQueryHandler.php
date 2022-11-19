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

namespace App\MessageHandler\States;

use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\States\GetStatesQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetStatesQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly StateRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetStatesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = match ($property) {
                GetStatesQuery::STATE_PROJECT     => $this->queryFilterByProjectId($dql, $value),
                GetStatesQuery::STATE_TEMPLATE    => $this->queryFilterByTemplateId($dql, $value),
                GetStatesQuery::STATE_NAME        => $this->queryFilterByName($dql, $value),
                GetStatesQuery::STATE_TYPE        => $this->queryFilterByType($dql, $value),
                GetStatesQuery::STATE_RESPONSIBLE => $this->queryFilterByResponsible($dql, $value),
                default                           => $dql,
            };
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(state.id)');
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
                'LOWER(state.name) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by state's project.
     */
    private function queryFilterByProjectId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('template.project = :project');
        $dql->setParameter('project', $value);

        return $dql;
    }

    /**
     * Alters query to filter by state's template.
     */
    private function queryFilterByTemplateId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('state.template = :template');
        $dql->setParameter('template', $value);

        return $dql;
    }

    /**
     * Alters query to filter by state name.
     */
    private function queryFilterByName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('state.name IS NULL');
        } else {
            $dql->andWhere('LOWER(state.name) LIKE LOWER(:name)');
            $dql->setParameter('name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by state type.
     */
    private function queryFilterByType(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('state.type IS NULL');
        } else {
            $dql->andWhere('LOWER(state.type) = LOWER(:type)');
            $dql->setParameter('type', $value);
        }

        return $dql;
    }

    /**
     * Alters query to filter by type of responsibility management.
     */
    private function queryFilterByResponsible(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('state.responsible IS NULL');
        } else {
            $dql->andWhere('LOWER(state.responsible) = LOWER(:responsible)');
            $dql->setParameter('responsible', $value);
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetStatesQuery::STATE_ID          => 'state.id',
            GetStatesQuery::STATE_PROJECT     => 'project.name',
            GetStatesQuery::STATE_TEMPLATE    => 'template.name',
            GetStatesQuery::STATE_NAME        => 'state.name',
            GetStatesQuery::STATE_TYPE        => 'state.type',
            GetStatesQuery::STATE_RESPONSIBLE => 'state.responsible',
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
