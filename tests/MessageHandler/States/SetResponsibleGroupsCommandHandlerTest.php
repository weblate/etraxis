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
use App\Entity\StateResponsibleGroup;
use App\LoginTrait;
use App\Message\States\SetResponsibleGroupsCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\SetResponsibleGroupsCommandHandler::__invoke
 */
final class SetResponsibleGroupsCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccessAppending(): void
    {
        $this->loginUser('admin@example.com');

        $before = [
            'Developers',
        ];

        $after = [
            'Developers',
            'Support Engineers',
        ];

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->responsibleGroupsToArray($state));

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            $developers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($state);
        self::assertSame($after, $this->responsibleGroupsToArray($state));
    }

    public function testSuccessReplacing(): void
    {
        $this->loginUser('admin@example.com');

        $before = [
            'Developers',
        ];

        $after = [
            'Support Engineers',
        ];

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->responsibleGroupsToArray($state));

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            $support->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($state);
        self::assertSame($after, $this->responsibleGroupsToArray($state));
    }

    public function testValidationInvalidGroups(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            'foo',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not valid.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set responsible groups.');

        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testFinalState(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set responsible groups.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [/* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand(self::UNKNOWN_ENTITY_ID, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup(): void
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Unknown group: Support Engineers');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'DESC']);

        $command = new SetResponsibleGroupsCommand($state->getId(), [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    private function responsibleGroupsToArray(State $state): array
    {
        $result = array_map(
            fn (StateResponsibleGroup $group) => $group->getGroup()->getName(),
            $state->getResponsibleGroups()->toArray()
        );

        sort($result);

        return $result;
    }
}
