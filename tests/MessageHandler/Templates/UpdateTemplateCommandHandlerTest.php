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

use App\Entity\Template;
use App\LoginTrait;
use App\Message\Templates\UpdateTemplateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\UpdateTemplateCommandHandler::__invoke
 */
final class UpdateTemplateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface         $commandBus;
    private TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand($template->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);

        /** @var Template $template */
        $template = $this->repository->find($template->getId());

        self::assertSame('Bugfix', $template->getName());
        self::assertSame('bug', $template->getPrefix());
        self::assertSame('Error reports', $template->getDescription());
        self::assertSame(5, $template->getCriticalAge());
        self::assertSame(10, $template->getFrozenTime());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this template.');

        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand($template->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        $command = new UpdateTemplateCommand(self::UNKNOWN_ENTITY_ID, 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand($template->getId(), 'Support', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testPrefixConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand($template->getId(), 'Bugfix', 'req', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }
}
