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
use App\Message\Dependencies\AddDependencyCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Dependencies\AddDependencyCommandHandler::__invoke
 */
final class AddDependencyCommandHandlerTest extends TransactionalTestCase
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
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));

        $command = new AddDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count + 1, $this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));
    }

    public function testSuccessExisting(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $existing */
        [/* skipping */ , /* skipping */ , $existing] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));

        $command = new AddDependencyCommand($issue->getId(), $existing->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($count, $this->doctrine->getRepository(Dependency::class)->findAllByIssue($issue));
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $command = new AddDependencyCommand(self::UNKNOWN_ENTITY_ID, $dependency->getId());

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to add dependencies.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);
    }

    public function testUnknownDependency(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown dependency.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddDependencyCommand($issue->getId(), self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);
    }

    public function testForbiddenDependency(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown dependency.');

        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $command = new AddDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);
    }

    public function testCrossDependency(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('These issues already depend on each other.');

        $this->loginUser('jkiehn@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddDependencyCommand($issue->getId(), $dependency->getId());

        $this->commandBus->handle($command);
    }
}
