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
}
