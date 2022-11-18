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

namespace App\MessageHandler\Issues;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\SecondsEnum;
use App\Entity\Issue;
use App\LoginTrait;
use App\Message\Issues\SuspendIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\SuspendIssueCommandHandler::__invoke
 */
final class SuspendIssueCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private IssueRepositoryInterface $repository;
    private \DateTime                $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Issue::class);

        $this->date = date_create();
        $this->date->setTimezone(timezone_open('UTC'));
        $this->date->setTimestamp(time() + SecondsEnum::OneDay->value);
        $this->date->setTime(0, 0);
    }

    public function testSuccess(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        self::assertFalse($issue->isSuspended());

        $events = count($issue->getEvents());

        $command = new SuspendIssueCommand($issue->getId(), $this->date->format('Y-m-d'));

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertTrue($issue->isSuspended());
        self::assertCount($events + 1, $issue->getEvents());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueSuspended, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
    }

    public function testValidationInvalidDate(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand($issue->getId(), $this->date->format('d-m-Y'));

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not valid.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationFutureDate(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Date must be in future.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand($issue->getId(), gmdate('Y-m-d'));

        $this->commandBus->handle($command);
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('ldoyle@example.com');

        $command = new SuspendIssueCommand(self::UNKNOWN_ENTITY_ID, $this->date->format('Y-m-d'));

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this issue.');

        $this->loginUser('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand($issue->getId(), $this->date->format('Y-m-d'));

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand($issue->getId(), $this->date->format('Y-m-d'));

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand($issue->getId(), $this->date->format('Y-m-d'));

        $this->commandBus->handle($command);
    }
}
