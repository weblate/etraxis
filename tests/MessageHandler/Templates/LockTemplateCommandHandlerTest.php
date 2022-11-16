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
use App\Message\Templates\LockTemplateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\LockTemplateCommandHandler::__invoke
 */
final class LockTemplateCommandHandlerTest extends TransactionalTestCase
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

    public function testLockTemplate(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        self::assertFalse($template->isLocked());

        $command = new LockTemplateCommand($template->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertTrue($template->isLocked());
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to lock this template.');

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertTrue($template->isLocked());

        $command = new LockTemplateCommand($template->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to lock this template.');

        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $command = new LockTemplateCommand($template->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        $command = new LockTemplateCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }
}
