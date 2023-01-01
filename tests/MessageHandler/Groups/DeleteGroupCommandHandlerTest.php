<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\MessageHandler\Groups;

use App\Entity\Group;
use App\LoginTrait;
use App\Message\Groups\DeleteGroupCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Groups\DeleteGroupCommandHandler::__invoke
 */
final class DeleteGroupCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private GroupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);
        self::assertNotNull($group);

        $command = new DeleteGroupCommand($group->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $group = $this->repository->find($command->getGroup());
        self::assertNull($group);
    }

    public function testUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteGroupCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this group.');

        $this->loginUser('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new DeleteGroupCommand($group->getId());

        $this->commandBus->handle($command);
    }
}
