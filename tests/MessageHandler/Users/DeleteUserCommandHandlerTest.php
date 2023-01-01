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

use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\DeleteUserCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\DeleteUserCommandHandler::__invoke
 */
final class DeleteUserCommandHandlerTest extends TransactionalTestCase
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

        $user = $this->repository->findOneByEmail('hstroman@example.com');
        self::assertNotNull($user);

        $command = new DeleteUserCommand($user->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $user = $this->repository->findOneByEmail('hstroman@example.com');
        self::assertNull($user);
    }

    public function testUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteUserCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this user.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('hstroman@example.com');

        $command = new DeleteUserCommand($user->getId());

        $this->commandBus->handle($command);
    }

    public function testForbidden(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this user.');

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('admin@example.com');

        $command = new DeleteUserCommand($user->getId());

        $this->commandBus->handle($command);
    }
}
