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

namespace App\MessageHandler\States;

use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use App\LoginTrait;
use App\Message\States\SetInitialStateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\SetInitialStateCommandHandler::__invoke
 */
final class SetInitialStateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                      $commandBus;
    private ObjectRepository|StateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $initial */
        [/* skipping */ , $initial] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        self::assertSame(StateTypeEnum::Initial, $initial->getType());
        self::assertNotSame(StateTypeEnum::Initial, $state->getType());

        $command = new SetInitialStateCommand($state->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($initial);
        $this->doctrine->getManager()->refresh($state);

        self::assertNotSame(StateTypeEnum::Initial, $initial->getType());
        self::assertSame(StateTypeEnum::Initial, $state->getType());
    }

    public function testInitialState(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertSame(StateTypeEnum::Initial, $state->getType());

        $command = new SetInitialStateCommand($state->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($state);

        self::assertSame(StateTypeEnum::Initial, $state->getType());
    }

    public function testUnknownState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        $command = new SetInitialStateCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set initial state.');

        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new SetInitialStateCommand($state->getId());

        $this->commandBus->handle($command);
    }
}
