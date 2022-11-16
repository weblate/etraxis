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

namespace App\MessageHandler\Users;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\CreateUserCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\CreateUserCommandHandler::__invoke
 */
final class CreateUserCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('anna@example.com');
        self::assertNull($user);

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        $this->commandBus->handle($command);

        $user = $this->repository->findOneByEmail('anna@example.com');
        self::assertInstanceOf(User::class, $user);

        $uuid_pattern = '/^([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})$/';
        self::assertSame(AccountProviderEnum::eTraxis, $user->getAccountProvider());
        self::assertMatchesRegularExpression($uuid_pattern, $user->getAccountUid());
        self::assertSame('anna@example.com', $user->getEmail());
        self::assertSame('Anna Rodygina', $user->getFullname());
        self::assertSame('Very lovely Daughter', $user->getDescription());
        self::assertFalse($user->isDisabled());
        self::assertTrue($user->isAdmin());
        self::assertSame(LocaleEnum::Russian, $user->getLocale());
        self::assertSame('Pacific/Auckland', $user->getTimezone());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new user.');

        $this->loginUser('artem@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            str_repeat('*', PasswordHasherInterface::MAX_PASSWORD_LENGTH + 1),
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'artem@example.com',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        $this->commandBus->handle($command);
    }
}
