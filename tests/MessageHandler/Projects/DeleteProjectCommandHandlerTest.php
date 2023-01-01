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
use App\Message\Projects\DeleteProjectCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Projects\DeleteProjectCommandHandler::__invoke
 */
final class DeleteProjectCommandHandlerTest extends TransactionalTestCase
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
        $project = $this->repository->findOneBy(['name' => 'Presto']);
        self::assertNotNull($project);

        $command = new DeleteProjectCommand($project->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $project = $this->repository->findOneBy(['name' => 'Presto']);
        self::assertNull($project);
    }

    public function testUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteProjectCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this project.');

        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Presto']);

        $command = new DeleteProjectCommand($project->getId());

        $this->commandBus->handle($command);
    }
}
