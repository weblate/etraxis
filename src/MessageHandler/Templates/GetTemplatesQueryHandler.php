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
            $dql = $this->queryFilter($dql, $property, $value);
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
     * Alters query to filter by the specified property.
     */
    private function queryFilter(QueryBuilder $dql, string $property, null|bool|int|string $value = null): QueryBuilder
    {
        switch ($property) {
            case GetTemplatesQuery::TEMPLATE_PROJECT:
                $dql->andWhere('template.project = :project');
                $dql->setParameter('project', (int) $value);

                break;

            case GetTemplatesQuery::TEMPLATE_NAME:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('template.name IS NULL');
                } else {
                    $dql->andWhere('LOWER(template.name) LIKE LOWER(:name)');
                    $dql->setParameter('name', "%{$value}%");
                }

                break;

            case GetTemplatesQuery::TEMPLATE_PREFIX:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('template.prefix IS NULL');
                } else {
                    $dql->andWhere('LOWER(template.prefix) LIKE LOWER(:prefix)');
                    $dql->setParameter('prefix', "%{$value}%");
                }

                break;

            case GetTemplatesQuery::TEMPLATE_DESCRIPTION:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('template.description IS NULL');
                } else {
                    $dql->andWhere('LOWER(template.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case GetTemplatesQuery::TEMPLATE_CRITICAL_AGE:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('template.criticalAge IS NULL');
                } else {
                    $dql->andWhere('template.criticalAge = :criticalAge');
                    $dql->setParameter('criticalAge', (int) $value);
                }

                break;

            case GetTemplatesQuery::TEMPLATE_FROZEN_TIME:
                if (0 === mb_strlen((string) $value)) {
                    $dql->andWhere('template.frozenTime IS NULL');
                } else {
                    $dql->andWhere('template.frozenTime = :frozenTime');
                    $dql->setParameter('frozenTime', (int) $value);
                }

                break;

            case GetTemplatesQuery::TEMPLATE_IS_LOCKED:
                $dql->andWhere('template.locked = :locked');
                $dql->setParameter('locked', (bool) $value);

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
            GetTemplatesQuery::TEMPLATE_ID           => 'template.id',
            GetTemplatesQuery::TEMPLATE_PROJECT      => 'project.name',
            GetTemplatesQuery::TEMPLATE_NAME         => 'template.name',
            GetTemplatesQuery::TEMPLATE_PREFIX       => 'template.prefix',
            GetTemplatesQuery::TEMPLATE_DESCRIPTION  => 'template.description',
            GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => 'template.criticalAge',
            GetTemplatesQuery::TEMPLATE_FROZEN_TIME  => 'template.frozenTime',
            GetTemplatesQuery::TEMPLATE_IS_LOCKED    => 'template.locked',
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
