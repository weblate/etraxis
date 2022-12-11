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

namespace App\Security\LDAP;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\LDAP\LdapUserLoader
 */
final class LdapUserLoaderTest extends TransactionalTestCase
{
    private LdapInterface           $ldap;
    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldap = new class() implements LdapInterface {
            public function findUser(string $email, string &$dn, string &$fullname): bool
            {
                if ('einstein@example.com' === $email) {
                    $dn       = 'uid=einstein,dc=example,dc=com';
                    $fullname = 'Albert Einstein';

                    return true;
                }

                if ('newton@example.com' === $email) {
                    $dn       = 'uid=newton,dc=example,dc=com';
                    $fullname = 'Isaac Newton';

                    return true;
                }

                return false;
            }

            public function checkCredentials(string $dn, string $password): bool
            {
                return true;
            }
        };

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testNewUser(): void
    {
        $userLoader = new LdapUserLoader($this->ldap, $this->commandBus, $this->repository);

        $count = count($this->repository->findAll());

        self::assertNull($this->repository->findOneByEmail('newton@example.com'));

        /** @var User $user */
        $user = $userLoader('newton@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame($user, $this->repository->findOneByEmail('newton@example.com'));
        self::assertCount($count + 1, $this->repository->findAll());

        self::assertSame('newton@example.com', $user->getEmail());
        self::assertSame('Isaac Newton', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('uid=newton,dc=example,dc=com', $user->getAccountUid());
    }

    /**
     * @covers ::__invoke
     */
    public function testExistingUser(): void
    {
        $userLoader = new LdapUserLoader($this->ldap, $this->commandBus, $this->repository);

        $count = count($this->repository->findAll());

        self::assertNull($this->repository->findOneByEmail('einstein@example.com'));
        self::assertNotNull($this->repository->findOneByEmail('einstein@ldap.forumsys.com'));

        /** @var User $user */
        $user = $userLoader('einstein@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame($user, $this->repository->findOneByEmail('einstein@example.com'));
        self::assertNull($this->repository->findOneByEmail('einstein@ldap.forumsys.com'));
        self::assertCount($count, $this->repository->findAll());

        self::assertSame('einstein@example.com', $user->getEmail());
        self::assertSame('Albert Einstein', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('uid=einstein,dc=example,dc=com', $user->getAccountUid());
    }

    /**
     * @covers ::__invoke
     */
    public function testUnknownUser(): void
    {
        $userLoader = new LdapUserLoader($this->ldap, $this->commandBus, $this->repository);

        $count = count($this->repository->findAll());

        self::assertNull($this->repository->findOneByEmail('unknown@example.com'));

        /** @var User $user */
        $user = $userLoader('unknown@example.com');

        self::assertNull($user);
        self::assertNull($this->repository->findOneByEmail('unknown@example.com'));
        self::assertCount($count, $this->repository->findAll());
    }
}
