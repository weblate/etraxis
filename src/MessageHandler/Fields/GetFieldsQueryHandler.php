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

namespace App\MessageHandler\Fields;

use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Fields\GetFieldsQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetFieldsQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly FieldRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetFieldsQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('field');

        // Include states.
        $dql->innerJoin('field.state', 'state');
        $dql->addSelect('state');

        // Include templates.
        $dql->innerJoin('state.template', 'template');
        $dql->addSelect('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Ignore removed fields.
        $dql->where('field.removedAt IS NULL');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(field.id)');
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
                'LOWER(field.name) LIKE :search',
                'LOWER(field.description) LIKE :search'
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
            case GetFieldsQuery::FIELD_PROJECT:
                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case GetFieldsQuery::FIELD_TEMPLATE:
                $dql->andWhere('state.template = :template');
                $dql->setParameter('template', (int) $value);

                break;

            case GetFieldsQuery::FIELD_STATE:
                $dql->andWhere('field.state= :state');
                $dql->setParameter('state', (int) $value);

                break;

            case GetFieldsQuery::FIELD_NAME:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('field.name IS NULL');
                } else {
                    $dql->andWhere('LOWER(field.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case GetFieldsQuery::FIELD_TYPE:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('field.type IS NULL');
                } else {
                    $dql->andWhere('LOWER(field.type) = LOWER(:type)');
                    $dql->setParameter('type', $value);
                }

                break;

            case GetFieldsQuery::FIELD_DESCRIPTION:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('field.description IS NULL');
                } else {
                    $dql->andWhere('LOWER(field.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case GetFieldsQuery::FIELD_POSITION:
                $dql->andWhere('field.position = :position');
                $dql->setParameter('position', (int) $value);

                break;

            case GetFieldsQuery::FIELD_REQUIRED:
                $dql->andWhere('field.required = :required');
                $dql->setParameter('required', (bool) $value);

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
            GetFieldsQuery::FIELD_ID          => 'field.id',
            GetFieldsQuery::FIELD_PROJECT     => 'project.name',
            GetFieldsQuery::FIELD_TEMPLATE    => 'template.name',
            GetFieldsQuery::FIELD_STATE       => 'state.name',
            GetFieldsQuery::FIELD_NAME        => 'field.name',
            GetFieldsQuery::FIELD_TYPE        => 'field.type',
            GetFieldsQuery::FIELD_DESCRIPTION => 'field.description',
            GetFieldsQuery::FIELD_POSITION    => 'field.position',
            GetFieldsQuery::FIELD_REQUIRED    => 'field.required',
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
