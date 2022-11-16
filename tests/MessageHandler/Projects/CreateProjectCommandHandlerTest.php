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

namespace App\MessageHandler\Projects;

use App\Entity\Project;
use App\LoginTrait;
use App\Message\Projects\CreateProjectCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Projects\CreateProjectCommandHandler::__invoke
 */
final class CreateProjectCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface        $commandBus;
    private ProjectRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertNull($project);

        $command = new CreateProjectCommand('Awesome Express', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertInstanceOf(Project::class, $project);

        self::assertSame('Awesome Express', $project->getName());
        self::assertSame('Newspaper-delivery company', $project->getDescription());
        self::assertTrue($project->isSuspended());
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new CreateProjectCommand(
            str_pad('', Project::MAX_NAME + 1),
            'Newspaper-delivery company',
            true
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

        $command = new CreateProjectCommand(
            'Awesome Express',
            str_pad('', Project::MAX_DESCRIPTION + 1),
            true
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
        $this->expectExceptionMessage('You are not allowed to create new project.');

        $this->loginUser('artem@example.com');

        $command = new CreateProjectCommand('Awesome Express', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Project with specified name already exists.');

        $this->loginUser('admin@example.com');

        $command = new CreateProjectCommand('Distinctio', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);
    }
}
