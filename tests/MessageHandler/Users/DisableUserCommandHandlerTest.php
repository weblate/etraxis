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

use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\DisableUserCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\DisableUserCommandHandler::__invoke
 */
final class DisableUserCommandHandlerTest extends TransactionalTestCase
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

        $user = $this->repository->findOneByEmail('nhills@example.com');

        self::assertFalse($user->isDisabled());

        $command = new DisableUserCommand($user->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertTrue($user->isDisabled());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to disable this user.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('nhills@example.com');

        $command = new DisableUserCommand($user->getId());

        $this->commandBus->handle($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new DisableUserCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testForbidden(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to disable this user.');

        $this->loginUser('admin@example.com');

        $admin = $this->repository->findOneByEmail('admin@example.com');

        $command = new DisableUserCommand($admin->getId());

        $this->commandBus->handle($command);
    }
}
