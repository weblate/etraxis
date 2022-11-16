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
