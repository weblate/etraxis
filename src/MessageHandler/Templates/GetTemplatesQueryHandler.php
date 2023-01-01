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

namespace App\MessageHandler\Templates;

use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Templates\GetTemplatesQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetTemplatesQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TemplateRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetTemplatesQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('template');

        // Include projects.
        $dql->innerJoin('template.project', 'project');
        $dql->addSelect('project');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = match ($property) {
                GetTemplatesQuery::TEMPLATE_PROJECT      => $this->queryFilterByProjectId($dql, $value),
                GetTemplatesQuery::TEMPLATE_NAME         => $this->queryFilterByName($dql, $value),
                GetTemplatesQuery::TEMPLATE_PREFIX       => $this->queryFilterByPrefix($dql, $value),
                GetTemplatesQuery::TEMPLATE_DESCRIPTION  => $this->queryFilterByDescription($dql, $value),
                GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => $this->queryFilterByCriticalAge($dql, $value),
                GetTemplatesQuery::TEMPLATE_FROZEN_TIME  => $this->queryFilterByFrozenTime($dql, $value),
                GetTemplatesQuery::TEMPLATE_IS_LOCKED    => $this->queryFilterByIsLocked($dql, $value),
                default                                  => $dql,
            };
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(template.id)');
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
                'LOWER(template.name) LIKE :search',
                'LOWER(template.prefix) LIKE :search',
                'LOWER(template.description) LIKE :search'
            ));

            $dql->setParameter('search', mb_strtolower("%{$search}%"));
        }

        return $dql;
    }

    /**
     * Alters query to filter by template's project.
     */
    private function queryFilterByProjectId(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        $dql->andWhere('template.project = :project');
        $dql->setParameter('project', $value);

        return $dql;
    }

    /**
     * Alters query to filter by template name.
     */
    private function queryFilterByName(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('template.name IS NULL');
        } else {
            $dql->andWhere('LOWER(template.name) LIKE LOWER(:name)');
            $dql->setParameter('name', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by template prefix.
     */
    private function queryFilterByPrefix(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('template.prefix IS NULL');
        } else {
            $dql->andWhere('LOWER(template.prefix) LIKE LOWER(:prefix)');
            $dql->setParameter('prefix', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by template description.
     */
    private function queryFilterByDescription(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 === mb_strlen($value ?? '')) {
            $dql->andWhere('template.description IS NULL');
        } else {
            $dql->andWhere('LOWER(template.description) LIKE LOWER(:description)');
            $dql->setParameter('description', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by critical age.
     */
    private function queryFilterByCriticalAge(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        if (null === $value) {
            $dql->andWhere('template.criticalAge IS NULL');
        } else {
            $dql->andWhere('template.criticalAge = :criticalAge');
            $dql->setParameter('criticalAge', $value);
        }

        return $dql;
    }

    /**
     * Alters query to filter by frozen time.
     */
    private function queryFilterByFrozenTime(QueryBuilder $dql, ?int $value): QueryBuilder
    {
        if (null === $value) {
            $dql->andWhere('template.frozenTime IS NULL');
        } else {
            $dql->andWhere('template.frozenTime = :frozenTime');
            $dql->setParameter('frozenTime', $value);
        }

        return $dql;
    }

    /**
     * Alters query to filter by template status.
     */
    private function queryFilterByIsLocked(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere('template.locked = :locked');
        $dql->setParameter('locked', (bool) $value);

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetTemplatesQuery::TEMPLATE_ID           => 'template.id',
            GetTemplatesQuery::TEMPLATE_PROJECT      => 'project.name',
            GetTemplatesQuery::TEMPLATE_NAME         => 'template.name',
            GetTemplatesQuery::TEMPLATE_PREFIX       => 'template.prefix',
            GetTemplatesQuery::TEMPLATE_DESCRIPTION  => 'template.description',
            GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => 'template.criticalAge',
            GetTemplatesQuery::TEMPLATE_FROZEN_TIME  => 'template.frozenTime',
            GetTemplatesQuery::TEMPLATE_IS_LOCKED    => 'template.locked',
            default                                  => null,
        };

        if ($order) {
            $dql->addOrderBy($order, AbstractCollectionQuery::SORT_DESC === mb_strtoupper($direction ?? '')
                ? AbstractCollectionQuery::SORT_DESC
                : AbstractCollectionQuery::SORT_ASC);
        }

        return $dql;
    }
}
