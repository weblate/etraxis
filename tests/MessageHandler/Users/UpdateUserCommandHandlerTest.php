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

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\UpdateUserCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\UpdateUserCommandHandler::__invoke
 */
final class UpdateUserCommandHandlerTest extends TransactionalTestCase
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        self::assertSame('Ted Berge', $user->getFullname());
        self::assertNotEmpty($user->getDescription());
        self::assertFalse($user->isAdmin());
        self::assertTrue($user->isDisabled());
        self::assertSame(LocaleEnum::English, $user->getLocale());
        self::assertSame('UTC', $user->getTimezone());

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            'Chaim Willms',
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
        );

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->getEmail());
        self::assertSame('Chaim Willms', $user->getFullname());
        self::assertEmpty($user->getDescription());
        self::assertTrue($user->isAdmin());
        self::assertFalse($user->isDisabled());
        self::assertSame(LocaleEnum::Russian, $user->getLocale());
        self::assertSame('Asia/Vladivostok', $user->getTimezone());
    }

    public function testValidationEmptyEmail(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            '',
            'Chaim Willms',
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            str_pad('@example.com', User::MAX_EMAIL + 1, 'a', STR_PAD_LEFT),
            'Chaim Willms',
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example',
            'Chaim Willms',
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not a valid email address.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyFullname(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            '',
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            str_pad('', User::MAX_FULLNAME + 1),
            null,
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            'Chaim Willms',
            str_pad('', User::MAX_DESCRIPTION + 1),
            true,
            false,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            'Chaim Willms',
            null,
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

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'chaim.willms@example.com',
            'Chaim Willms',
            null,
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
        $this->expectExceptionMessage('You are not allowed to update this user.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('nhills@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            $user->getEmail(),
            $user->getFullname(),
            $user->getDescription(),
            $user->isAdmin(),
            !$user->isDisabled(),
            $user->getLocale(),
            $user->getTimezone()
        );

        $this->commandBus->handle($command);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new UpdateUserCommand(
            self::UNKNOWN_ENTITY_ID,
            'chaim.willms@example.com',
            'Chaim Willms',
            null,
            true,
            true,
            LocaleEnum::Russian,
            'Asia/Vladivostok'
        );

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('nhills@example.com');

        $command = new UpdateUserCommand(
            $user->getId(),
            'vparker@example.com',
            $user->getFullname(),
            $user->getDescription(),
            $user->isAdmin(),
            !$user->isDisabled(),
            $user->getLocale(),
            $user->getTimezone()
        );

        $this->commandBus->handle($command);
    }
}
