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

namespace App\MessageHandler\Groups;

use App\Entity\Group;
use App\LoginTrait;
use App\Message\Groups\UpdateGroupCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Groups\UpdateGroupCommandHandler::__invoke
 */
final class UpdateGroupCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private GroupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testLocalSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand($group->getId(), 'Programmers', 'Software Engineers');

        $this->commandBus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->getId());

        self::assertSame('Programmers', $group->getName());
        self::assertSame('Software Engineers', $group->getDescription());
    }

    public function testGlobalSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand($group->getId(), 'All my slaves', 'Human beings');

        $this->commandBus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->getId());

        self::assertSame('All my slaves', $group->getName());
        self::assertSame('Human beings', $group->getDescription());
    }

    public function testValidationEmptyName(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand(
            $group->getId(),
            '',
            'Software Engineers'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand(
            $group->getId(),
            str_pad('', Group::MAX_NAME + 1),
            'Software Engineers'
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 25 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationDescriptionLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand(
            $group->getId(),
            'Programmers',
            str_pad('', Group::MAX_DESCRIPTION + 1)
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 100 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this group.');

        $this->loginUser('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand($group->getId(), 'Programmers', 'Software Engineers');

        $this->commandBus->handle($command);
    }

    public function testUnknownGroup(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown group.');

        $this->loginUser('admin@example.com');

        $command = new UpdateGroupCommand(self::UNKNOWN_ENTITY_ID, 'Programmers', 'Software Engineers');

        $this->commandBus->handle($command);
    }

    public function testLocalGroupConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with entered name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand($group->getId(), 'Company Staff', null);

        try {
            $this->commandBus->handle($command);
        } catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand($group->getId(), 'Managers', null);

        $this->commandBus->handle($command);
    }

    public function testGlobalGroupConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with entered name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand($group->getId(), 'Managers', null);

        try {
            $this->commandBus->handle($command);
        } catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand($group->getId(), 'Company Clients', null);

        $this->commandBus->handle($command);
    }
}
