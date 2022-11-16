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

namespace App\MessageHandler\ListItems;

use App\Entity\ListItem;
use App\LoginTrait;
use App\Message\ListItems\UpdateListItemCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\ListItems\UpdateListItemCommandHandler::__invoke
 */
final class UpdateListItemCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccessValue(): void
    {
        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        self::assertSame(3, $item->getValue());
        self::assertSame('low', $item->getText());

        $command = new UpdateListItemCommand($item->getId(), 5, 'low');

        $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->find($item->getId());

        self::assertSame(5, $item->getValue());
        self::assertSame('low', $item->getText());
    }

    public function testSuccessText(): void
    {
        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(1, $item->getValue());
        self::assertSame('high', $item->getText());

        $command = new UpdateListItemCommand($item->getId(), 1, 'critical');

        $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->find($item->getId());

        self::assertSame(1, $item->getValue());
        self::assertSame('critical', $item->getText());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this list item.');

        $this->loginUser('artem@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand($item->getId(), 1, 'critical');

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this list item.');

        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand($item->getId(), 1, 'critical');

        $this->commandBus->handle($command);
    }

    public function testUnknownItem(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown list item.');

        $this->loginUser('admin@example.com');

        $command = new UpdateListItemCommand(self::UNKNOWN_ENTITY_ID, 1, 'critical');

        $this->commandBus->handle($command);
    }

    public function testValueConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified value already exists.');

        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand($item->getId(), 2, 'critical');

        $this->commandBus->handle($command);
    }

    public function testTextConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified text already exists.');

        $this->loginUser('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new UpdateListItemCommand($item->getId(), 1, 'normal');

        $this->commandBus->handle($command);
    }
}
