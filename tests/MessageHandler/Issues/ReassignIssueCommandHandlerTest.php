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
use App\Entity\Issue;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Issues\ReassignIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\ReassignIssueCommandHandler::__invoke
 */
final class ReassignIssueCommandHandlerTest extends TransactionalTestCase
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
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $events = count($issue->getEvents());

        self::assertSame('akoepp@example.com', $issue->getResponsible()->getEmail());

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('nhills@example.com', $issue->getResponsible()->getEmail());

        self::assertCount($events + 1, $issue->getEvents());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueReassigned, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertSame($user->getFullname(), $event->getParameter());
    }

    public function testSameResponsible(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'akoepp@example.com']);

        $events = count($issue->getEvents());

        self::assertSame('akoepp@example.com', $issue->getResponsible()->getEmail());

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('akoepp@example.com', $issue->getResponsible()->getEmail());
        self::assertCount($events, $issue->getEvents());
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand(self::UNKNOWN_ENTITY_ID, $user->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $command = new ReassignIssueCommand($issue->getId(), self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to reassign this issue.');

        $this->loginUser('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);
    }

    public function testResponsibleDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The issue cannot be assigned to specified user.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to reassign this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to reassign this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand($issue->getId(), $user->getId());

        $this->commandBus->handle($command);
    }
}
