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
use App\LoginTrait;
use App\Message\Fields\UpdateFieldCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\UpdateFieldCommandHandler::__invoke
 */
final class UpdateFieldCommandHandlerTest extends TransactionalTestCase
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

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        self::assertSame('Issue ID', $field->getName());
        self::assertNull($field->getDescription());
        self::assertTrue($field->isRequired());

        $command = new UpdateFieldCommand($field->getId(), 'Task ID', 'ID of the duplicating task.', false, null);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->find($field->getId());

        self::assertSame('Task ID', $field->getName());
        self::assertSame('ID of the duplicating task.', $field->getDescription());
        self::assertFalse($field->isRequired());
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand(
            $field->getId(),
            str_pad('', Field::MAX_NAME + 1),
            'ID of the duplicating task.',
            false,
            null
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 50 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationDescriptionLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand(
            $field->getId(),
            'Task ID',
            str_pad('', Field::MAX_DESCRIPTION + 1),
            false,
            null
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 1000 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        $command = new UpdateFieldCommand(self::UNKNOWN_ENTITY_ID, 'Task ID', 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testRemovedField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand($field->getId(), 'Task ID', 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this field.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand($field->getId(), 'Task ID', 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this field.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand($field->getId(), 'Task ID', 'ID of the duplicating task.', true, null);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Field with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new UpdateFieldCommand($field->getId(), 'Description', null, true, null);

        $this->commandBus->handle($command);
    }
}
