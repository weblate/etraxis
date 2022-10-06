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
use App\Message\Projects\UpdateProjectCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Projects\UpdateProjectCommandHandler::__invoke
 */
final class UpdateProjectCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                        $commandBus;
    private ObjectRepository|ProjectRepositoryInterface $repository;

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
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $command = new UpdateProjectCommand($project->getId(), 'Awesome Express', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);

        /** @var Project $project */
        $project = $this->repository->find($project->getId());

        self::assertSame('Awesome Express', $project->getName());
        self::assertSame('Newspaper-delivery company', $project->getDescription());
        self::assertTrue($project->isSuspended());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this project.');

        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $command = new UpdateProjectCommand($project->getId(), 'Awesome Express', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);
    }

    public function testUnknownProject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginUser('admin@example.com');

        $command = new UpdateProjectCommand(self::UNKNOWN_ENTITY_ID, 'Awesome Express', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Project with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $command = new UpdateProjectCommand($project->getId(), 'Molestiae', 'Newspaper-delivery company', true);

        $this->commandBus->handle($command);
    }
}
