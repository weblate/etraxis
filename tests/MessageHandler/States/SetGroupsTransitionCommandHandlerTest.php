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

use App\Entity\Group;
use App\Entity\State;
use App\Entity\StateGroupTransition;
use App\LoginTrait;
use App\Message\States\SetGroupsTransitionCommand;
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
 * @covers \App\MessageHandler\States\SetGroupsTransitionCommandHandler::__invoke
 */
final class SetGroupsTransitionCommandHandlerTest extends TransactionalTestCase
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
            'Managers',
            'Support Engineers',
        ];

        $after = [
            'Developers',
            'Support Engineers',
        ];

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->transitionsToArray($fromState->getGroupTransitions(), $toState));

        $command = new SetGroupsTransitionCommand($fromState->getId(), $toState->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($fromState);
        self::assertSame($after, $this->transitionsToArray($fromState->getGroupTransitions(), $toState));
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set state transitions.');

        $this->loginUser('artem@example.com');

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand($fromState->getId(), $toState->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownFromState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand(self::UNKNOWN_ENTITY_ID, $toState->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownToState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand($fromState->getId(), self::UNKNOWN_ENTITY_ID, [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongStates(): void
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('States must belong the same template.');

        $this->loginUser('admin@example.com');

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'DESC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand($fromState->getId(), $toState->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup(): void
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Unknown group: Developers');

        $this->loginUser('admin@example.com');

        /** @var \App\Repository\Contracts\GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $groupRepository->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        /** @var Group $support */
        [$support] = $groupRepository->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand($fromState->getId(), $toState->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    private function transitionsToArray(Collection $transitions, State $state): array
    {
        $filtered = array_filter($transitions->toArray(), fn (StateGroupTransition $transition) => $transition->getToState() === $state);
        $result   = array_map(fn (StateGroupTransition $transition) => $transition->getGroup()->getName(), $filtered);

        sort($result);

        return $result;
    }
}
