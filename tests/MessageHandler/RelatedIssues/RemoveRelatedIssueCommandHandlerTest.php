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

namespace App\MessageHandler\RelatedIssues;

use App\Entity\Issue;
use App\Entity\RelatedIssue;
use App\LoginTrait;
use App\Message\RelatedIssues\RemoveRelatedIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\RelatedIssues\RemoveRelatedIssueCommandHandler::__invoke
 */
final class RemoveRelatedIssueCommandHandlerTest extends TransactionalTestCase
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
        $this->loginUser('dquigley@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(RelatedIssue::class)->getRelatedIssues($issue));

        $command = new RemoveRelatedIssueCommand($issue->getId(), $relatedIssue->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count - 1, $this->doctrine->getRepository(RelatedIssue::class)->getRelatedIssues($issue));
    }

    public function testSuccessMissing(): void
    {
        $this->loginUser('dquigley@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(RelatedIssue::class)->getRelatedIssues($issue));

        $command = new RemoveRelatedIssueCommand($issue->getId(), $relatedIssue->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count, $this->doctrine->getRepository(RelatedIssue::class)->getRelatedIssues($issue));
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('dquigley@example.com');

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $command = new RemoveRelatedIssueCommand(self::UNKNOWN_ENTITY_ID, $relatedIssue->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to remove related issues.');

        $this->loginUser('akoepp@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $command = new RemoveRelatedIssueCommand($issue->getId(), $relatedIssue->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownRelatedIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown related issue.');

        $this->loginUser('dquigley@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $command = new RemoveRelatedIssueCommand($issue->getId(), self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testForbiddenRelatedIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown related issue.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $command = new RemoveRelatedIssueCommand($issue->getId(), $relatedIssue->getId());

        $this->commandBus->handle($command);
    }
}
