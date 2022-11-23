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

namespace App\MessageHandler\Groups;

use App\Entity\Group;
use App\Entity\Project;
use App\LoginTrait;
use App\Message\Groups\CreateGroupCommand;
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
 * @covers \App\MessageHandler\Groups\CreateGroupCommandHandler::__invoke
 */
final class CreateGroupCommandHandlerTest extends TransactionalTestCase
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

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertNull($group);

        $command = new CreateGroupCommand($project->getId(), 'Testers', 'Test Engineers');

        $result = $this->commandBus->handleWithResult($command);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertInstanceOf(Group::class, $group);
        self::assertSame($group, $result);

        self::assertSame($project, $group->getProject());
        self::assertSame('Testers', $group->getName());
        self::assertSame('Test Engineers', $group->getDescription());
    }

    public function testGlobalSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertNull($group);

        $command = new CreateGroupCommand(null, 'Testers', 'Test Engineers');

        $result = $this->commandBus->handleWithResult($command);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertInstanceOf(Group::class, $group);
        self::assertSame($group, $result);

        self::assertNull($group->getProject());
        self::assertSame('Testers', $group->getName());
        self::assertSame('Test Engineers', $group->getDescription());
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateGroupCommand(
            null,
            str_pad('', Group::MAX_NAME + 1),
            'Test Engineers'
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

        $command = new CreateGroupCommand(
            null,
            'Testers',
            str_pad('', Group::MAX_DESCRIPTION + 1)
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 100 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownProject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginUser('admin@example.com');

        $command = new CreateGroupCommand(self::UNKNOWN_ENTITY_ID, 'Testers', 'Test Engineers');

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new group.');

        $this->loginUser('artem@example.com');

        $command = new CreateGroupCommand(null, 'Testers', 'Test Engineers');

        $this->commandBus->handle($command);
    }

    public function testLocalGroupConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateGroupCommand($project->getId(), 'Company Staff', null);

        try {
            $this->commandBus->handle($command);
        } catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new CreateGroupCommand($project->getId(), 'Developers', null);

        $this->commandBus->handle($command);
    }

    public function testGlobalGroupConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginUser('admin@example.com');

        $command = new CreateGroupCommand(null, 'Developers', null);

        try {
            $this->commandBus->handle($command);
        } catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new CreateGroupCommand(null, 'Company Staff', null);

        $this->commandBus->handle($command);
    }
}
