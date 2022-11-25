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

namespace App\MessageHandler\Comments;

use App\Entity\Comment;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Issue;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Comments\AddCommentCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Comments\AddCommentCommandHandler::__invoke
 */
final class AddCommentCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('jmueller@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'jmueller@example.com']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $events   = count($issue->getEvents());
        $comments = count($this->doctrine->getRepository(Comment::class)->findAll());

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($events + 1, $issue->getEvents());
        self::assertCount($comments + 1, $this->doctrine->getRepository(Comment::class)->findAll());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::PublicComment, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertNull($event->getParameter());

        /** @var Comment $comment */
        $comment = $this->doctrine->getRepository(Comment::class)->findOneBy(['event' => $event]);

        self::assertSame('Test comment.', $comment->getBody());
        self::assertFalse($comment->isPrivate());
    }

    public function testValidationEmptyBody(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), '', false);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationBodyLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), str_pad('', Comment::MAX_BODY + 1), false);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 10000 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('jmueller@example.com');

        $command = new AddCommentCommand(self::UNKNOWN_ENTITY_ID, 'Test comment.', false);

        $this->commandBus->handle($command);
    }

    public function testPublicCommentAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);
    }

    public function testPrivateCommentAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue privately.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', true);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginUser('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $command = new AddCommentCommand($issue->getId(), 'Test comment.', false);

        $this->commandBus->handle($command);
    }
}
