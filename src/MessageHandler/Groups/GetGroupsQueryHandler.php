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

namespace App\MessageHandler\Groups;

use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Groups\GetGroupsQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetGroupsQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly GroupRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetGroupsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('grp');

        // Include projects.
        $dql->leftJoin('grp.project', 'project');
        $dql->addSelect('project');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(grp.id)');
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
                'LOWER(grp.name) LIKE :search',
                'LOWER(grp.description) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by the specified property.
     */
    private function queryFilter(QueryBuilder $dql, string $property, null|bool|int|string $value = null): QueryBuilder
    {
        switch ($property) {
            case GetGroupsQuery::GROUP_PROJECT:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('grp.project IS NULL');
                } else {
                    $dql->andWhere('grp.project = :project');
                    $dql->setParameter('project', (int) $value);
                }

                break;

            case GetGroupsQuery::GROUP_NAME:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('grp.name IS NULL');
                } else {
                    $dql->andWhere('LOWER(grp.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case GetGroupsQuery::GROUP_DESCRIPTION:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('grp.description IS NULL');
                } else {
                    $dql->andWhere('LOWER(grp.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case GetGroupsQuery::GROUP_IS_GLOBAL:
                $dql->andWhere($value ? 'grp.project IS NULL' : 'grp.project IS NOT NULL');

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
            GetGroupsQuery::GROUP_ID          => 'grp.id',
            GetGroupsQuery::GROUP_PROJECT     => 'project.name',
            GetGroupsQuery::GROUP_NAME        => 'grp.name',
            GetGroupsQuery::GROUP_DESCRIPTION => 'grp.description',
            GetGroupsQuery::GROUP_IS_GLOBAL   => 'project.id - project.id',
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
