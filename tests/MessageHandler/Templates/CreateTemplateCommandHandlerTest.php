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

namespace App\MessageHandler\Templates;

use App\Entity\Project;
use App\Entity\Template;
use App\LoginTrait;
use App\Message\Templates\CreateTemplateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\CreateTemplateCommandHandler::__invoke
 */
final class CreateTemplateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                         $commandBus;
    private ObjectRepository|TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertNull($template);

        $command = new CreateTemplateCommand($project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertInstanceOf(Template::class, $template);

        self::assertSame($project, $template->getProject());
        self::assertSame('Bugfix', $template->getName());
        self::assertSame('bug', $template->getPrefix());
        self::assertSame('Error reports', $template->getDescription());
        self::assertSame(5, $template->getCriticalAge());
        self::assertSame(10, $template->getFrozenTime());
        self::assertTrue($template->isLocked());
    }

    public function testUnknownProject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginUser('admin@example.com');

        $command = new CreateTemplateCommand(self::UNKNOWN_ENTITY_ID, 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new template.');

        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand($project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand($project->getId(), 'Development', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testPrefixConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand($project->getId(), 'Bugfix', 'task', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }
}
