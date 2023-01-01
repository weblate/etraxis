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
use Symfony\Component\Messenger\Exception\ValidationFailedException;
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

        $result = $this->commandBus->handleWithResult($command);

        $user = $this->repository->findOneByEmail('anna@example.com');
        self::assertInstanceOf(User::class, $user);
        self::assertSame($user, $result);

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

    public function testValidationEmptyEmail(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            '',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmailLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            str_pad('@example.com', User::MAX_EMAIL + 1, 'a', STR_PAD_LEFT),
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 254 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidEmail(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not a valid email address.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyPassword(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            '',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyFullname(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            '',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationFullnameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            str_pad('', User::MAX_FULLNAME + 1),
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 50 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationDescriptionLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            'Anna Rodygina',
            str_pad('', User::MAX_DESCRIPTION + 1),
            true,
            false,
            LocaleEnum::Russian,
            'Pacific/Auckland'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 100 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyTimezone(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            ''
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidTimezone(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateUserCommand(
            'anna@example.com',
            'secret',
            'Anna Rodygina',
            'Very lovely Daughter',
            true,
            false,
            LocaleEnum::Russian,
            'Invalid/Timezone'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('The value you selected is not a valid choice.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
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
