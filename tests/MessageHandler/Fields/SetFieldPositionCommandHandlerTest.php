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
use App\Message\Fields\SetFieldPositionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Fields\SetFieldPositionCommandHandler
 */
final class SetFieldPositionCommandHandlerTest extends TransactionalTestCase
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

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessUp(): void
    {
        $this->loginUser('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), $field->getPosition() - 1);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->getState()));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessDown(): void
    {
        $this->loginUser('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), $field->getPosition() + 1);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->getState()));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessTop(): void
    {
        $this->loginUser('admin@example.com');

        $expected = [
            'Effort',
            'Commit ID',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), 1);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->getState()));
    }

    /**
     * @covers ::__invoke
     * @covers ::setPosition
     */
    public function testSuccessBottom(): void
    {
        $this->loginUser('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Test coverage',
            'Delta',
        ];

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), PHP_INT_MAX);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->getState()));
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this field.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), 1);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this field.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), 1);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testUnknownField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        $command = new SetFieldPositionCommand(self::UNKNOWN_ENTITY_ID, 1);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::__invoke
     */
    public function testRemovedField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand($field->getId(), 1);

        $this->commandBus->handle($command);
    }

    /**
     * Returns fields of specified state ordered by their position.
     */
    private function getFields(State $state): array
    {
        /** @var Field[] $fields */
        $fields = $this->repository->findBy([
            'state'     => $state,
            'removedAt' => null,
        ], ['position' => 'ASC']);

        return array_map(fn (Field $field) => $field->getName(), $fields);
    }
}
