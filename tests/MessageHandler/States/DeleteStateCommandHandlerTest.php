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

namespace App\MessageHandler\States;

use App\Entity\State;
use App\LoginTrait;
use App\Message\States\DeleteStateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\DeleteStateCommandHandler::__invoke
 */
final class DeleteStateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private StateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);
        self::assertNotNull($state);

        $command = new DeleteStateCommand($state->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $state = $this->repository->find($command->getState());
        self::assertNull($state);
    }

    public function testUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteStateCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this state.');

        $this->loginUser('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $command = new DeleteStateCommand($state->getId());

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this state.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new DeleteStateCommand($state->getId());

        $this->commandBus->handle($command);
    }
}
