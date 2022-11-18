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

use App\Entity\Issue;
use App\Entity\Watcher;
use App\LoginTrait;
use App\Message\Issues\WatchIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\WatchIssueCommandHandler::__invoke
 */
final class WatchIssueCommandHandlerTest extends TransactionalTestCase
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
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new WatchIssueCommand($issue->getId());

        $this->commandBus->handle($command);

        self::assertCount($count + 1, $this->doctrine->getRepository(Watcher::class)->findAll());
    }

    public function testSuccessWatching(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new WatchIssueCommand($issue->getId());

        $this->commandBus->handle($command);

        self::assertCount($count, $this->doctrine->getRepository(Watcher::class)->findAll());
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('tmarquardt@example.com');

        $command = new WatchIssueCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to watch this issue.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new WatchIssueCommand($issue->getId());

        $this->commandBus->handle($command);
    }
}
