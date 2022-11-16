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

use App\Entity\Field;
use App\Entity\ListItem;
use App\LoginTrait;
use App\Message\ListItems\CreateListItemCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\ListItems\CreateListItemCommandHandler::__invoke
 */
final class CreateListItemCommandHandlerTest extends TransactionalTestCase
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

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        $item = $this->repository->findOneBy(['value' => 4]);
        self::assertNull($item);

        $command = new CreateListItemCommand($field->getId(), 4, 'typo');

        $this->commandBus->handle($command);

        /** @var ListItem $item */
        $item = $this->repository->findOneBy(['value' => 4]);
        self::assertInstanceOf(ListItem::class, $item);

        self::assertSame($field, $item->getField());
        self::assertSame(4, $item->getValue());
        self::assertSame('typo', $item->getText());
    }

    public function testUnknownField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        $command = new CreateListItemCommand(self::UNKNOWN_ENTITY_ID, 4, 'typo');

        $this->commandBus->handle($command);
    }

    public function testWrongField(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new list item.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new CreateListItemCommand($field->getId(), 4, 'typo');

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new list item.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand($field->getId(), 4, 'typo');

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new list item.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand($field->getId(), 4, 'typo');

        $this->commandBus->handle($command);
    }

    public function testValueConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified value already exists.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand($field->getId(), 3, 'typo');

        $this->commandBus->handle($command);
    }

    public function testTextConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Item with specified text already exists.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new CreateListItemCommand($field->getId(), 4, 'low');

        $this->commandBus->handle($command);
    }
}
