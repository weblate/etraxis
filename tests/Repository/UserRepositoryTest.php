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
}
