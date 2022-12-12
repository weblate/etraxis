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
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\UserRepository
 */
final class UserRepositoryTest extends TransactionalTestCase
{
    private Contracts\UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::upgradePassword
     */
    public function testUpgradePassword(): void
    {
        $user = $this->repository->findOneByEmail('admin@example.com');

        $newPassword = md5('secret');

        self::assertNotSame($newPassword, $user->getPassword());

        $this->repository->upgradePassword($user, $newPassword);

        self::assertSame($newPassword, $user->getPassword());
    }

    /**
     * @covers ::findOneByEmail
     */
    public function testFindOneByEmail(): void
    {
        $user = $this->repository->findOneByEmail('artem@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('artem@example.com', $user->getEmail());

        $user = $this->repository->findOneByEmail('404@example.com');

        self::assertNull($user);
    }

    /**
     * @covers ::findOneByResetToken
     */
    public function testFindOneByResetToken(): void
    {
        $user = $this->repository->findOneByResetToken('artem@example.com');

        self::assertNull($user);

        $user  = $this->repository->findOneByEmail('artem@example.com');
        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        $user2 = $this->repository->findOneByResetToken($token);

        self::assertInstanceOf(User::class, $user2);
        self::assertSame($user, $user2);
    }

    /**
     * @covers ::findOneByProviderUid
     */
    public function testFindOneByProviderUid(): void
    {
        $user = $this->repository->findOneByProviderUid(AccountProviderEnum::LDAP, 'uid=einstein,dc=example,dc=com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('einstein@ldap.forumsys.com', $user->getEmail());

        $user = $this->repository->findOneByProviderUid(AccountProviderEnum::eTraxis, 'uid=einstein,dc=example,dc=com');

        self::assertNull($user);
    }
}
