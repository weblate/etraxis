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

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

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
     * @see \Symfony\Component\Security\Core\User\PasswordUpgraderInterface::upgradePassword
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if ($user instanceof User) {
            $user->setPassword($newHashedPassword);
            $this->persist($user, true);
        }
    }

    /**
     * @see Contracts\UserRepositoryInterface::findOneByEmail
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => $email,
        ]);
    }

    /**
     * @see Contracts\UserRepositoryInterface::findOneByResetToken
     */
    public function findOneByResetToken(string $token): ?User
    {
        return $this->findOneBy([
            'resetToken' => $token,
        ]);
    }

    /**
     * @see Contracts\UserRepositoryInterface::findOneByProviderUid
     */
    public function findOneByProviderUid(AccountProviderEnum $provider, string $uid): ?User
    {
        return $this->findOneBy([
            'accountProvider' => $provider,
            'accountUid'      => $uid,
        ]);
    }
}
