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

namespace App\MessageHandler\ListItems;

use App\Entity\ListItem;
use App\LoginTrait;
use App\Message\ListItems\DeleteListItemCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\ListItems\DeleteListItemCommandHandler::__invoke
 */
final class DeleteListItemCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface         $commandBus;
    private ListItemRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);
        self::assertNotNull($item);

        $command = new DeleteListItemCommand($item->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $item = $this->repository->find($command->getItem());
        self::assertNull($item);
    }

    public function testUnknownItem(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteListItemCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this list item.');

        $this->loginUser('artem@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $command = new DeleteListItemCommand($item->getId());

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this list item.');

        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $command = new DeleteListItemCommand($item->getId());

        $this->commandBus->handle($command);
    }
}
