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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\State;
use App\Entity\StateRoleTransition;
use App\LoginTrait;
use App\Message\States\SetRolesTransitionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\SetRolesTransitionCommandHandler::__invoke
 */
final class SetRolesTransitionCommandHandlerTest extends TransactionalTestCase
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

        $before = [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ];

        $after = [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ];

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        self::assertSame($before, $this->transitionsToArray($fromState->getRoleTransitions(), $toState));

        $command = new SetRolesTransitionCommand($fromState->getId(), $toState->getId(), [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($fromState);
        self::assertSame($after, $this->transitionsToArray($fromState->getRoleTransitions(), $toState));
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set state transitions.');

        $this->loginUser('artem@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand($fromState->getId(), $toState->getId(), [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownFromState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand(self::UNKNOWN_ENTITY_ID, $toState->getId(), [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownToState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand($fromState->getId(), self::UNKNOWN_ENTITY_ID, [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongStates(): void
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('States must belong the same template.');

        $this->loginUser('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new SetRolesTransitionCommand($fromState->getId(), $toState->getId(), [
            SystemRoleEnum::Anyone,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    private function transitionsToArray(Collection $transitions, State $state): array
    {
        $filtered = array_filter($transitions->toArray(), fn (StateRoleTransition $transition) => $transition->getToState() === $state);
        $result   = array_map(fn (StateRoleTransition $transition) => $transition->getRole(), $filtered);

        usort($result, fn (SystemRoleEnum $r1, SystemRoleEnum $r2) => strcmp($r1->value, $r2->value));

        return $result;
    }
}
