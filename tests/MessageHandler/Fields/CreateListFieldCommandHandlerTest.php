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

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\CreateFieldCommandHandler::__invoke
 */
final class CreateListFieldCommandHandlerTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Severity']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Severity', FieldTypeEnum::List, null, true, [
            Field::DEFAULT => 1,
        ]);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Severity']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::List, $field->getType());

        $strategy = $field->getStrategy();
        self::assertSame(1, $strategy->getParameter(Field::DEFAULT));
    }

    public function testSuccessFallback(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Severity']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Severity', FieldTypeEnum::List, null, true, null);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Severity']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::List, $field->getType());

        $strategy = $field->getStrategy();
        self::assertNull($strategy->getParameter(Field::DEFAULT));
    }
}
