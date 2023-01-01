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
            $dql = match ($property) {
                GetUsersQuery::USER_EMAIL       => $this->queryFilterByEmail($dql, $value),
                GetUsersQuery::USER_FULLNAME    => $this->queryFilterByFullname($dql, $value),
                GetUsersQuery::USER_DESCRIPTION => $this->queryFilterByDescription($dql, $value),
                GetUsersQuery::USER_IS_ADMIN    => $this->queryFilterByIsAdmin($dql, $value),
                GetUsersQuery::USER_IS_DISABLED => $this->queryFilterByIsDisabled($dql, $value),
                GetUsersQuery::USER_PROVIDER    => $this->queryFilterByProvider($dql, $value),
                default                         => $dql,
            };
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
     * Alters query to filter by user's email.
     */
    private function queryFilterByEmail(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(user.email) LIKE LOWER(:email)');
            $dql->setParameter('email', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by user's full name.
     */
    private function queryFilterByFullname(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(user.fullname) LIKE LOWER(:fullname)');
            $dql->setParameter('fullname', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by user description.
     */
    private function queryFilterByDescription(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (0 !== mb_strlen($value ?? '')) {
            $dql->andWhere('LOWER(user.description) LIKE LOWER(:description)');
            $dql->setParameter('description', "%{$value}%");
        }

        return $dql;
    }

    /**
     * Alters query to filter by user role.
     */
    private function queryFilterByIsAdmin(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere('user.admin = :admin');
        $dql->setParameter('admin', (bool) $value);

        return $dql;
    }

    /**
     * Alters query to filter by user status.
     */
    private function queryFilterByIsDisabled(QueryBuilder $dql, ?bool $value): QueryBuilder
    {
        $dql->andWhere('user.disabled = :disabled');
        $dql->setParameter('disabled', (bool) $value);

        return $dql;
    }

    /**
     * Alters query to filter by account provider.
     */
    private function queryFilterByProvider(QueryBuilder $dql, ?string $value): QueryBuilder
    {
        if (AccountProviderEnum::tryFrom($value ?? '')) {
            $dql->andWhere('LOWER(user.accountProvider) = LOWER(:provider)');
            $dql->setParameter('provider', $value);
        }

        return $dql;
    }

    /**
     * Alters query in accordance with the specified sorting.
     */
    private function queryOrder(QueryBuilder $dql, string $property, ?string $direction): QueryBuilder
    {
        $order = match ($property) {
            GetUsersQuery::USER_ID          => 'user.id',
            GetUsersQuery::USER_EMAIL       => 'user.email',
            GetUsersQuery::USER_FULLNAME    => 'user.fullname',
            GetUsersQuery::USER_DESCRIPTION => 'user.description',
            GetUsersQuery::USER_IS_ADMIN    => 'user.admin',
            GetUsersQuery::USER_PROVIDER    => 'user.accountProvider',
            default                         => null,
        };

        if ($order) {
            $dql->addOrderBy($order, AbstractCollectionQuery::SORT_DESC === mb_strtoupper($direction ?? '')
                ? AbstractCollectionQuery::SORT_DESC
                : AbstractCollectionQuery::SORT_ASC);
        }

        return $dql;
    }
}
