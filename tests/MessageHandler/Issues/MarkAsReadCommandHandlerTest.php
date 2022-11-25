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
use App\Entity\LastRead;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Issues\MarkAsReadCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\MarkAsReadCommandHandler::__invoke
 */
final class MarkAsReadCommandHandlerTest extends TransactionalTestCase
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

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var LastRead $lastRead */
        $lastRead = $this->doctrine->getRepository(LastRead::class)->findOneBy([
            'issue' => $read,
            'user'  => $user,
        ]);

        self::assertGreaterThan(2, time() - $lastRead->getReadAt());

        $count = count($this->doctrine->getRepository(LastRead::class)->findAll());

        $command = new MarkAsReadCommand([
            $read->getId(),
            $unread->getId(),
            $forbidden->getId(),
            self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($lastRead);

        self::assertCount($count + 1, $this->doctrine->getRepository(LastRead::class)->findAll());
        self::assertLessThanOrEqual(2, time() - $lastRead->getReadAt());
    }

    public function testEmpty(): void
    {
        $this->loginUser('tmarquardt@example.com');

        $count = count($this->doctrine->getRepository(LastRead::class)->findAll());

        $command = new MarkAsReadCommand([
            self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertCount($count, $this->doctrine->getRepository(LastRead::class)->findAll());
    }

    public function testValidationIssuesCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('tmarquardt@example.com');

        $command = new MarkAsReadCommand([]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidIssues(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('tmarquardt@example.com');

        $command = new MarkAsReadCommand([
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
