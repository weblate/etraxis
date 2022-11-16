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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\State;
use App\LoginTrait;
use App\Message\Fields\CreateFieldCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\CreateFieldCommandHandler::__invoke
 */
final class CreateFieldCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Task ID', 'removedAt' => null]);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Task ID', FieldTypeEnum::Issue, 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Task ID', 'removedAt' => null]);
        self::assertInstanceOf(Field::class, $field);

        self::assertSame($state, $field->getState());
        self::assertSame('Task ID', $field->getName());
        self::assertSame(FieldTypeEnum::Issue, $field->getType());
        self::assertSame('ID of the duplicating task.', $field->getDescription());
        self::assertSame(2, $field->getPosition());
        self::assertTrue($field->isRequired());
    }

    public function testUnknownState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('admin@example.com');

        $command = new CreateFieldCommand(self::UNKNOWN_ENTITY_ID, 'Task ID', FieldTypeEnum::Issue, 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new field.');

        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateFieldCommand($state->getId(), 'Task ID', FieldTypeEnum::Issue, 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new field.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateFieldCommand($state->getId(), 'Task ID', FieldTypeEnum::Issue, 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Field with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new CreateFieldCommand($state->getId(), 'Issue ID', FieldTypeEnum::Issue, 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }
}
