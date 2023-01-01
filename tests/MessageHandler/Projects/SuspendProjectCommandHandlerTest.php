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

namespace App\MessageHandler\Projects;

use App\Entity\Project;
use App\LoginTrait;
use App\Message\Projects\SuspendProjectCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Projects\SuspendProjectCommandHandler::__invoke
 */
final class SuspendProjectCommandHandlerTest extends TransactionalTestCase
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

    public function testSuspendProject(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        self::assertFalse($project->isSuspended());

        $command = new SuspendProjectCommand($project->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($project);
        self::assertTrue($project->isSuspended());
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this project.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        self::assertTrue($project->isSuspended());

        $command = new SuspendProjectCommand($project->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this project.');

        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        $command = new SuspendProjectCommand($project->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownProject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginUser('admin@example.com');

        $command = new SuspendProjectCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }
}
