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

namespace App\Repository;

use App\Entity\Change;
use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'Change' entities repository.
 */
class ChangeRepository extends ServiceEntityRepository implements Contracts\ChangeRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Change::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(Change $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(Change $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @see Contracts\ChangeRepositoryInterface::findAllByIssue
     */
    public function findAllByIssue(Issue $issue, User $user): array
    {
        $query = $this->createQueryBuilder('change')
            ->select('change')

            ->innerJoin('change.event', 'event')
            ->addSelect('event')

            ->innerJoin('event.issue', 'issue')
            ->addSelect('issue')

            ->innerJoin('event.user', 'user')
            ->addSelect('user')

            ->leftJoin('change.field', 'field')
            ->addSelect('field')

            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->addOrderBy('field.position', 'ASC')
            ->addOrderBy('field.removedAt', 'ASC')
        ;

        // Retrieve only fields the user is allowed to see.
        $query
            ->leftJoin('field.rolePermissions', 'frp_anyone', Join::WITH, 'frp_anyone.role = :role_anyone')
            ->leftJoin('field.rolePermissions', 'frp_author', Join::WITH, 'frp_author.role = :role_author')
            ->leftJoin('field.rolePermissions', 'frp_responsible', Join::WITH, 'frp_responsible.role = :role_responsible')
            ->leftJoin('field.groupPermissions', 'fgp')
            ->andWhere($query->expr()->orX(
                'change.field IS NULL',
                $query->expr()->in('frp_anyone.permission', ':permissions'),
                $query->expr()->andX(
                    'issue.author = :user',
                    $query->expr()->in('frp_author.permission', ':permissions')
                ),
                $query->expr()->andX(
                    'issue.responsible = :user',
                    $query->expr()->in('frp_responsible.permission', ':permissions')
                ),
                $query->expr()->andX(
                    $query->expr()->in('fgp.group', ':groups'),
                    $query->expr()->in('fgp.permission', ':permissions')
                ),
            ))
        ;

        $query->setParameters([
            'role_anyone'      => SystemRoleEnum::Anyone->value,
            'role_author'      => SystemRoleEnum::Author->value,
            'role_responsible' => SystemRoleEnum::Responsible->value,
            'permissions'      => [FieldPermissionEnum::ReadOnly->value, FieldPermissionEnum::ReadAndWrite->value],
            'issue'            => $issue,
            'user'             => $user,
            'groups'           => $user->getGroups(),
        ]);

        return $query->getQuery()->getResult();
    }
}
