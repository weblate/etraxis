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
            $dql = match ($property) {
                GetFieldsQuery::FIELD_PROJECT     => $this->queryFilterByProjectId($dql, $value),
                GetFieldsQuery::FIELD_TEMPLATE    => $this->queryFilterByTemplateId($dql, $value),
                GetFieldsQuery::FIELD_STATE       => $this->queryFilterByStateId($dql, $value),
                GetFieldsQuery::FIELD_NAME        => $this->queryFilterByName($dql, $value),
                GetFieldsQuery::FIELD_TYPE        => $this->queryFilterByType($dql, $value),
                GetFieldsQuery::FIELD_DESCRIPTION => $this->queryFilterByDescription($dql, $value),
                GetFieldsQuery::FIELD_POSITION    => $this->queryFilterByPosition($dql, $value),
                GetFieldsQuery::FIELD_IS_REQUIRED => $this->queryFilterByIsRequired($dql, $value),
                default                           => $dql,
            };
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
     * Alters query to filter by field's project.
     */
    private function queryFilterByProjectId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('template.project = :project');
        $dql->setParameter('project', $value);

        return $dql;
    }

    /**
     * Alters query to filter by field's template.
     */
    private function queryFilterByTemplateId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('state.template = :template');
        $dql->setParameter('template', $value);

        return $dql;
    }

    /**
     * Alters query to filter by field's state.
     */
    private function queryFilterByStateId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('field.state= :state');
        $dql->setParameter('state', $value);

        return $dql;
    }

    /**
     * Alters query to filter by field name.
     */
    private function queryFilterByName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('field.name IS NULL');
        } else {
            $dql->andWhere('LOWER(field.name) LIKE LOWER(:name)');
            $dql->setParameter('name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by field type.
     */
    private function queryFilterByType(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('field.type IS NULL');
        } else {
            $dql->andWhere('LOWER(field.type) = LOWER(:type)');
            $dql->setParameter('type', $value);
        }

        return $dql;
    }

    /**
     * Alters query to filter by field description.
     */
    private function queryFilterByDescription(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('field.description IS NULL');
        } else {
            $dql->andWhere('LOWER(field.description) LIKE LOWER(:description)');
            $dql->setParameter('description', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by field position.
     */
    private function queryFilterByPosition(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('field.position = :position');
        $dql->setParameter('position', $value);

        return $dql;
    }

    /**
     * Alters query to filter by required flag.
     */
    private function queryFilterByIsRequired(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere('field.required = :required');
        $dql->setParameter('required', (bool) $value);

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetFieldsQuery::FIELD_ID          => 'field.id',
            GetFieldsQuery::FIELD_PROJECT     => 'project.name',
            GetFieldsQuery::FIELD_TEMPLATE    => 'template.name',
            GetFieldsQuery::FIELD_STATE       => 'state.name',
            GetFieldsQuery::FIELD_NAME        => 'field.name',
            GetFieldsQuery::FIELD_TYPE        => 'field.type',
            GetFieldsQuery::FIELD_DESCRIPTION => 'field.description',
            GetFieldsQuery::FIELD_POSITION    => 'field.position',
            GetFieldsQuery::FIELD_IS_REQUIRED => 'field.required',
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
