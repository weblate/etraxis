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

namespace App\MessageHandler\Fields;

use App\Entity\Field;
use App\Entity\State;
use App\LoginTrait;
use App\Message\Fields\DeleteFieldCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\DeleteFieldCommandHandler::__invoke
 */
final class DeleteFieldCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private FieldRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccessDelete(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(3, $state->getFields());

        [$field1, $field2, $field3] = $state->getFields()->getValues();

        self::assertSame(1, $field1->getPosition());
        self::assertSame(2, $field2->getPosition());
        self::assertSame(3, $field3->getPosition());

        $command = new DeleteFieldCommand($field1->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->getField());
        self::assertNull($field);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(2, $state->getFields());

        [$field1, $field2] = $state->getFields()->getValues();

        self::assertSame(1, $field1->getPosition());
        self::assertSame(2, $field2->getPosition());
    }

    public function testSuccessRemove(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertCount(3, $state->getFields());

        [$field1, $field2, $field3] = $state->getFields()->getValues();

        self::assertSame(1, $field1->getPosition());
        self::assertSame(2, $field2->getPosition());
        self::assertSame(3, $field3->getPosition());

        $command = new DeleteFieldCommand($field1->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        /** @var Field $field */
        $field = $this->repository->find($command->getField());
        self::assertNotNull($field);
        self::assertTrue($field->isRemoved());
        self::assertSame(1, $field->getPosition());

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertCount(2, $state->getFields());

        [$field1, $field2] = $state->getFields()->getValues();

        self::assertSame(1, $field1->getPosition());
        self::assertSame(2, $field2->getPosition());
    }

    public function testUnknownField(): void
    {
        $this->loginUser('admin@example.com');

        $command = new DeleteFieldCommand(self::UNKNOWN_ENTITY_ID);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testRemovedField(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'DESC']);

        self::assertCount(1, $field->getState()->getFields());

        $command = new DeleteFieldCommand($field->getId());

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->getField());

        self::assertNotNull($field);
        self::assertTrue($field->isRemoved());
        self::assertCount(1, $field->getState()->getFields());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this field.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $command = new DeleteFieldCommand($field->getId());

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this field.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $field->getState()->getTemplate()->setLocked(false);

        $command = new DeleteFieldCommand($field->getId());

        $this->commandBus->handle($command);
    }
}
