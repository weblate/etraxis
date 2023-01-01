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

namespace App\Security;

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\UserChecker
 */
final class UserCheckerTest extends WebTestCase
{
    private UserCheckerInterface    $userChecker;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userChecker = new UserChecker();

        $doctrine = self::getContainer()->get('doctrine');

        $this->repository = $doctrine->getRepository(User::class);
    }

    /**
     * @covers ::checkPreAuth
     */
    public function testCheckPreAuthSuccess(): void
    {
        $user = $this->repository->findOneByEmail('artem@example.com');
        $this->userChecker->checkPreAuth($user);

        self::assertTrue(true);
    }

    /**
     * @covers ::checkPreAuth
     */
    public function testCheckPreAuthNotUser(): void
    {
        $user = new InMemoryUser('artem@example.com', 'secret');
        $this->userChecker->checkPreAuth($user);

        self::assertTrue(true);
    }

    /**
     * @covers ::checkPreAuth
     */
    public function testCheckPreAuthDisabledUser(): void
    {
        $this->expectException(AccountStatusException::class);
        $this->expectExceptionMessage('Account is disabled.');

        $user = $this->repository->findOneByEmail('tberge@example.com');
        $this->userChecker->checkPreAuth($user);
    }

    /**
     * @covers ::checkPostAuth
     */
    public function testCheckPostAuth(): void
    {
        $user = $this->repository->findOneByEmail('artem@example.com');
        $this->userChecker->checkPostAuth($user);

        self::assertTrue(true);
    }
}
