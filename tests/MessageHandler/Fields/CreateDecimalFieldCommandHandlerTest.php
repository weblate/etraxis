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

namespace App\MessageHandler\Fields;

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\State;
use App\LoginTrait;
use App\Message\Fields\CreateFieldCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\CreateFieldCommandHandler::__invoke
 */
final class CreateDecimalFieldCommandHandlerTest extends TransactionalTestCase
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
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Coverage', FieldTypeEnum::Decimal, null, true, [
            Field::MINIMUM => '0.00',
            Field::MAXIMUM => '100.00',
            Field::DEFAULT => '3.1415',
        ]);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::Decimal, $field->getType());

        $strategy = $field->getStrategy();
        self::assertSame('0.00', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('100.00', $strategy->getParameter(Field::MAXIMUM));
        self::assertSame('3.1415', $strategy->getParameter(Field::DEFAULT));
    }

    public function testSuccessFallback(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Coverage', FieldTypeEnum::Decimal, null, true, null);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::Decimal, $field->getType());

        $strategy = $field->getStrategy();
        self::assertSame('-9999999999.9999999999', $strategy->getParameter(Field::MINIMUM));
        self::assertSame('9999999999.9999999999', $strategy->getParameter(Field::MAXIMUM));
        self::assertNull($strategy->getParameter(Field::DEFAULT));
    }

    public function testDefaultValueRange(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Coverage', FieldTypeEnum::Decimal, null, true, [
            Field::MINIMUM => '0.00',
            Field::MAXIMUM => '100.00',
            Field::DEFAULT => '-3.1415',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('Default value should be in range from 0.00 to 100.00.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testMinMaxValues(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Coverage', FieldTypeEnum::Decimal, null, true, [
            Field::MINIMUM => '100.00',
            Field::MAXIMUM => '0.00',
            Field::DEFAULT => '3.1415',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('Maximum value should not be less then minimum one.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }
}
