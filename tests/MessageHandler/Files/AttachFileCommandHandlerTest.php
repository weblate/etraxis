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
use App\Entity\File;
use App\Entity\Issue;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Files\AttachFileCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Files\AttachFileCommandHandler::__invoke
 */
final class AttachFileCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private const MEGABYTE = 1024 * 1024;

    private CommandBusInterface      $commandBus;
    private IssueRepositoryInterface $repository;
    private UploadedFile             $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Issue::class);

        $filename = sprintf('%s/var/_%s', getcwd(), md5('test.txt'));
        file_put_contents($filename, str_repeat('*', self::MEGABYTE * 2));
        $this->file = new UploadedFile($filename, 'test.txt', 'text/plain', null, true);
    }

    protected function tearDown(): void
    {
        foreach (['test.txt', 'huge.txt'] as $basename) {
            $filename = sprintf('%s/var/_%s', getcwd(), md5($basename));

            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        parent::tearDown();
    }

    public function testSuccess(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $events = count($issue->getEvents());
        $files  = count($this->doctrine->getRepository(File::class)->findAll());

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($events + 1, $issue->getEvents());
        self::assertCount($files + 1, $this->doctrine->getRepository(File::class)->findAll());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::FileAttached, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertSame('test.txt', $event->getParameter());

        /** @var File $file */
        $file = $this->doctrine->getRepository(File::class)->findOneBy(['event' => $event]);

        self::assertSame('test.txt', $file->getFileName());
        self::assertSame(self::MEGABYTE * 2, $file->getFileSize());
        self::assertSame('text/plain', $file->getMimeType());
        self::assertMatchesRegularExpression('/^([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})$/', $file->getUid());
        self::assertFalse($file->isRemoved());

        $filename = sprintf('var%s%s', \DIRECTORY_SEPARATOR, $file->getUid());
        self::assertFileExists($filename);
        unlink($filename);
    }

    public function testMaxSize(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The file size must not exceed 2 MB.');

        $this->loginUser('ldoyle@example.com');

        $filename = sprintf('%s/var/_%s', getcwd(), md5('huge.txt'));
        file_put_contents($filename, str_repeat('*', self::MEGABYTE * 2 + 1));
        $file = new UploadedFile($filename, 'huge.txt', 'text/plain', null, true);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new AttachFileCommand($issue->getId(), $file);

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('ldoyle@example.com');

        $command = new AttachFileCommand(self::UNKNOWN_ENTITY_ID, $this->file);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginUser('akoepp@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to attach a file to this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $issue->getTemplate()->setFrozenTime(1);

        $command = new AttachFileCommand($issue->getId(), $this->file);

        $this->commandBus->handle($command);
    }
}
