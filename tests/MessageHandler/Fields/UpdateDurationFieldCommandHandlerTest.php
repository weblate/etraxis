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
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\UpdateFieldCommandHandler::__invoke
 */
final class UpdateDurationFieldCommandHandlerTest extends TransactionalTestCase
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
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort']);

        $strategy = $field->getStrategy();

        self::assertSame('0:01', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('160:00', $strategy->getParameter(Field::MAXIMUM));
        self::assertSame('8:00', $strategy->getParameter(Field::DEFAULT));

        $command = new UpdateFieldCommand($field->getId(), $field->getName(), $field->getDescription(), $field->isRequired(), [
            Field::MINIMUM => '1:00',
            Field::MAXIMUM => '8:00',
            Field::DEFAULT => '1:30',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame('1:00', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('8:00', $strategy->getParameter(Field::MAXIMUM));
        self::assertSame('1:30', $strategy->getParameter(Field::DEFAULT));
    }

    public function testSuccessFallback(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort']);

        $strategy = $field->getStrategy();

        self::assertSame('0:01', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('160:00', $strategy->getParameter(Field::MAXIMUM));
        self::assertSame('8:00', $strategy->getParameter(Field::DEFAULT));

        $command = new UpdateFieldCommand($field->getId(), $field->getName(), $field->getDescription(), $field->isRequired(), null);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame('0:01', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('160:00', $strategy->getParameter(Field::MAXIMUM));
        self::assertSame('8:00', $strategy->getParameter(Field::DEFAULT));
    }

    public function testDefaultValueRange(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort']);

        $command = new UpdateFieldCommand($field->getId(), $field->getName(), $field->getDescription(), $field->isRequired(), [
            Field::MINIMUM => '0:00',
            Field::MAXIMUM => '23:59',
            Field::DEFAULT => '24:00',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('Default value should be in range from 0:00 to 23:59.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testMinMaxValues(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Effort']);

        $command = new UpdateFieldCommand($field->getId(), $field->getName(), $field->getDescription(), $field->isRequired(), [
            Field::MINIMUM => '23:59',
            Field::MAXIMUM => '0:00',
            Field::DEFAULT => '8:00',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('Maximum value should not be less then minimum one.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }
}
