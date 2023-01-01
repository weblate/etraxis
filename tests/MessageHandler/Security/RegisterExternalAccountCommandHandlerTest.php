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

namespace App\MessageHandler\Security;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use App\Message\Security\RegisterExternalAccountCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Security\RegisterExternalAccountCommandHandler::__invoke
 */
final class RegisterExternalAccountCommandHandlerTest extends TransactionalTestCase
{
    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testNewUser(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('anna@example.com');
        self::assertNull($user);

        $command = new RegisterExternalAccountCommand('anna@example.com', 'Anna Rodygina', AccountProviderEnum::LDAP, 'ldap-a56eb4e9');

        $result = $this->commandBus->handleWithResult($command);

        $user = $this->repository->findOneByEmail('anna@example.com');
        self::assertInstanceOf(User::class, $user);
        self::assertSame($user, $result);

        self::assertSame('anna@example.com', $user->getEmail());
        self::assertSame('Anna Rodygina', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('ldap-a56eb4e9', $user->getAccountUid());
        self::assertSame(LocaleEnum::English, $user->getLocale());
    }

    public function testExistingUserByUid(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        self::assertSame('einstein@ldap.forumsys.com', $user->getEmail());
        self::assertSame('Albert Einstein', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('uid=einstein,dc=example,dc=com', $user->getAccountUid());

        $command = new RegisterExternalAccountCommand('anna@example.com', 'Anna Rodygina', AccountProviderEnum::LDAP, 'uid=einstein,dc=example,dc=com');

        $result = $this->commandBus->handleWithResult($command);

        $this->doctrine->getManager()->refresh($user);
        self::assertSame($user, $result);

        self::assertSame('anna@example.com', $user->getEmail());
        self::assertSame('Anna Rodygina', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('uid=einstein,dc=example,dc=com', $user->getAccountUid());
    }

    public function testExistingUserByEmail(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');
        self::assertNotNull($user);

        self::assertSame('artem@example.com', $user->getEmail());
        self::assertSame('Artem Rodygin', $user->getFullname());
        self::assertSame(AccountProviderEnum::eTraxis, $user->getAccountProvider());
        self::assertNotSame('ldap-a56eb4e9', $user->getAccountUid());

        $command = new RegisterExternalAccountCommand('artem@example.com', 'Tomas Rodriges', AccountProviderEnum::LDAP, 'ldap-a56eb4e9');

        $result = $this->commandBus->handleWithResult($command);

        $this->doctrine->getManager()->refresh($user);
        self::assertSame($user, $result);

        self::assertSame('artem@example.com', $user->getEmail());
        self::assertSame('Tomas Rodriges', $user->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
        self::assertSame('ldap-a56eb4e9', $user->getAccountUid());
    }
}
