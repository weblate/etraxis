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
use App\Message\Issues\UnwatchIssuesCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\UnwatchIssuesCommandHandler::__invoke
 */
final class UnwatchIssuesCommandHandlerTest extends TransactionalTestCase
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

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new UnwatchIssuesCommand([
            $watching->getId(),
            $unwatching->getId(),
            self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertCount($count - 1, $this->doctrine->getRepository(Watcher::class)->findAll());
    }

    public function testValidationIssuesCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('fdooley@example.com');

        $command = new UnwatchIssuesCommand([]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This collection should contain 1 element or more.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidIssues(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('fdooley@example.com');

        $command = new UnwatchIssuesCommand([
            'foo',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not valid.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }
}
