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
use App\Message\Users\EnableUserCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\EnableUserCommandHandler::__invoke
 */
final class EnableUserCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                     $commandBus;
    private ObjectRepository|UserRepositoryInterface $repository;

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

        self::assertTrue($user->isDisabled());

        $command = new EnableUserCommand($user->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($user->isDisabled());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to enable this user.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new EnableUserCommand($user->getId());

        $this->commandBus->handle($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new EnableUserCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }
}
