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
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\CreateFieldCommandHandler::__invoke
 */
final class CreateStringFieldCommandHandlerTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Subject', FieldTypeEnum::String, null, true, [
            Field::LENGTH       => 100,
            Field::DEFAULT      => 'Message subject',
            Field::PCRE_CHECK   => '[0-9a-f]+',
            Field::PCRE_SEARCH  => '(\d{3})-(\d{3})-(\d{4})',
            Field::PCRE_REPLACE => '($1) $2-$3',
        ]);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::String, $field->getType());

        $strategy = $field->getStrategy();
        self::assertSame(100, $strategy->getParameter(Field::LENGTH));
        self::assertSame('Message subject', $strategy->getParameter(Field::DEFAULT));
        self::assertSame('[0-9a-f]+', $strategy->getParameter(Field::PCRE_CHECK));
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $strategy->getParameter(Field::PCRE_SEARCH));
        self::assertSame('($1) $2-$3', $strategy->getParameter(Field::PCRE_REPLACE));
    }

    public function testSuccessFallback(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Subject', FieldTypeEnum::String, null, true, null);

        $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        self::assertInstanceOf(Field::class, $field);
        self::assertSame(FieldTypeEnum::String, $field->getType());

        $strategy = $field->getStrategy();
        self::assertSame(250, $strategy->getParameter(Field::LENGTH));
        self::assertNull($strategy->getParameter(Field::DEFAULT));
        self::assertNull($strategy->getParameter(Field::PCRE_CHECK));
        self::assertNull($strategy->getParameter(Field::PCRE_SEARCH));
        self::assertNull($strategy->getParameter(Field::PCRE_REPLACE));
    }

    public function testDefaultValueLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Subject']);
        self::assertNull($field);

        $command = new CreateFieldCommand($state->getId(), 'Subject', FieldTypeEnum::String, null, true, [
            Field::LENGTH  => 10,
            Field::DEFAULT => 'Message subject',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('Default value should not be longer than 10 characters.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }
}
