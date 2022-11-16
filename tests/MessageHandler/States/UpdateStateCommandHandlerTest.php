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

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\State;
use App\LoginTrait;
use App\Message\States\UpdateStateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\UpdateStateCommandHandler::__invoke
 */
final class UpdateStateCommandHandlerTest extends TransactionalTestCase
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
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand(
            $state->getId(),
            'Forwarded',
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);

        /** @var State $state */
        $state = $this->repository->find($state->getId());

        self::assertSame('Forwarded', $state->getName());
        self::assertSame(StateResponsibleEnum::Keep, $state->getResponsible());
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand(
            $state->getId(),
            str_pad('', State::MAX_NAME + 1),
            StateResponsibleEnum::Keep
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 50 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this state.');

        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand(
            $state->getId(),
            'Forwarded',
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this state.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand(
            $state->getId(),
            'Forwarded',
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testUnknownState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        $command = new UpdateStateCommand(
            self::UNKNOWN_ENTITY_ID,
            'Forwarded',
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('State with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand(
            $state->getId(),
            'Completed',
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }
}
