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

namespace App\Repository;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'User' entities repository.
 */
class UserRepository extends ServiceEntityRepository implements Contracts\UserRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function refresh(User $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => $email,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByResetToken(string $token): ?User
    {
        return $this->findOneBy([
            'resetToken' => $token,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByProviderUid(AccountProviderEnum $provider, string $uid): ?User
    {
        return $this->findOneBy([
            'accountProvider' => $provider,
            'accountUid'      => $uid,
        ]);
    }
}
