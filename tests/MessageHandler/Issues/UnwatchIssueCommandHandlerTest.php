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
use App\Message\Issues\UnwatchIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\UnwatchIssueCommandHandler::__invoke
 */
final class UnwatchIssueCommandHandlerTest extends TransactionalTestCase
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
        $this->loginUser('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new UnwatchIssueCommand($issue->getId());

        $this->commandBus->handle($command);

        self::assertCount($count - 1, $this->doctrine->getRepository(Watcher::class)->findAll());
    }

    public function testSuccessWatching(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new UnwatchIssueCommand($issue->getId());

        $this->commandBus->handle($command);

        self::assertCount($count, $this->doctrine->getRepository(Watcher::class)->findAll());
    }
}
