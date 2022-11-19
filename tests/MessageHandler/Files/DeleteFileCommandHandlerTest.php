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

namespace App\MessageHandler\Files;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\SecondsEnum;
use App\Entity\File;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Files\DeleteFileCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Files\DeleteFileCommandHandler::__invoke
 */
final class DeleteFileCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface     $commandBus;
    private FileRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(File::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        self::assertNotNull($file);
        self::assertFalse($file->isRemoved());

        $filename = sprintf('var%s%s', \DIRECTORY_SEPARATOR, $file->getUid());
        file_put_contents($filename, str_repeat('*', $file->getFileSize()));
        self::assertFileExists($filename);

        $events = count($file->getEvent()->getIssue()->getEvents());
        $files  = count($this->repository->findAll());

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($file->getEvent()->getIssue());

        self::assertCount($events + 1, $file->getEvent()->getIssue()->getEvents());
        self::assertCount($files, $this->repository->findAll());

        /** @var \App\Entity\Event $event */
        $event = $file->getEvent()->getIssue()->getEvents()->last();

        self::assertSame(EventTypeEnum::FileDeleted, $event->getType());
        self::assertSame($file->getEvent()->getIssue(), $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertSame($file->getFileName(), $event->getParameter());

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        self::assertNotNull($file);
        self::assertTrue($file->isRemoved());
        self::assertFileDoesNotExist($filename);
    }

    public function testUnknownFile(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown file.');

        $this->loginUser('ldoyle@example.com');

        $command = new DeleteFileCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testRemovedFile(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown file.');

        $this->loginUser('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginUser('fdooley@example.com');

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginUser('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginUser('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginUser('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->getEvent()->getIssue()->suspend(time() + SecondsEnum::OneDay->value);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this file.');

        $this->loginUser('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->repository->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $file->getEvent()->getIssue()->getTemplate()->setFrozenTime(1);

        $command = new DeleteFileCommand($file->getId());

        $this->commandBus->handle($command);
    }
}
