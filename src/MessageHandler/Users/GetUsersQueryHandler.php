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

namespace App\MessageHandler\Users;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Collection;
use App\Message\Users\GetUsersQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Query handler.
 */
final class GetUsersQueryHandler implements QueryHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Query handler.
     *
     * @throws AccessDeniedHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(GetUsersQuery $query): Collection
    {
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException('You do not have required permissions.');
        }

        $dql = $this->repository->createQueryBuilder('user');

        // Search.
        $dql = $this->querySearch($dql, $query->getSearch());

        // Filter.
        foreach ($query->getFilters() as $property => $value) {
            $dql = $this->queryFilter($dql, $property, $value);
        }

        // Total number of entities.
        $count = clone $dql;
        $count->select('COUNT(user.id)');
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
                'LOWER(user.email) LIKE :search',
                'LOWER(user.fullname) LIKE :search',
                'LOWER(user.description) LIKE :search',
                'LOWER(user.accountProvider) LIKE :search'
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
            case GetUsersQuery::USER_EMAIL:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(user.email) LIKE LOWER(:email)');
                    $dql->setParameter('email', "%{$value}%");
                }

                break;

            case GetUsersQuery::USER_FULLNAME:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(user.fullname) LIKE LOWER(:fullname)');
                    $dql->setParameter('fullname', "%{$value}%");
                }

                break;

            case GetUsersQuery::USER_DESCRIPTION:
                if (0 !== mb_strlen((string) $value)) {
                    $dql->andWhere('LOWER(user.description) LIKE LOWER(:description)');
                    $dql->setParameter('description', "%{$value}%");
                }

                break;

            case GetUsersQuery::USER_ADMIN:
                $dql->andWhere('user.admin = :admin');
                $dql->setParameter('admin', (bool) $value);

                break;

            case GetUsersQuery::USER_DISABLED:
                $dql->andWhere('user.disabled = :disabled');
                $dql->setParameter('disabled', (bool) $value);

                break;

            case GetUsersQuery::USER_PROVIDER:
                if (AccountProviderEnum::tryFrom($value)) {
                    $dql->andWhere('LOWER(user.accountProvider) = LOWER(:provider)');
                    $dql->setParameter('provider', $value);
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
            GetUsersQuery::USER_ID          => 'user.id',
            GetUsersQuery::USER_EMAIL       => 'user.email',
            GetUsersQuery::USER_FULLNAME    => 'user.fullname',
            GetUsersQuery::USER_DESCRIPTION => 'user.description',
            GetUsersQuery::USER_ADMIN       => 'user.admin',
            GetUsersQuery::USER_PROVIDER    => 'user.accountProvider',
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
