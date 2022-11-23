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

namespace App\MessageHandler\Dependencies;

use App\Entity\Dependency;
use App\Entity\Issue;
use App\LoginTrait;
use App\Message\Dependencies\RemoveDependencyCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Dependencies\RemoveDependencyCommandHandler::__invoke
 */
final class RemoveDependencyCommandHandlerTest extends TransactionalTestCase
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
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));

        $command = new RemoveDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);

        self::assertCount($count - 1, $this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));
    }

    public function testSuccessMissing(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));

        $command = new RemoveDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);

        self::assertCount($count, $this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $command = new RemoveDependencyCommand(self::UNKNOWN_ENTITY_ID, $dependency->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to remove dependencies.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $command = new RemoveDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownDependency(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown dependency.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new RemoveDependencyCommand($issue->getId(), self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testForbiddenDependency(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown dependency.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $command = new RemoveDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);
    }
}
