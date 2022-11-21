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

namespace App\MessageHandler\Issues;

use App\Entity\Change;
use App\Entity\DecimalValue;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\StringValue;
use App\Entity\TextValue;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Issues\UpdateIssueCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\UpdateIssueCommandHandler::__invoke
 */
final class UpdateIssueCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \App\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \App\Repository\Contracts\StringValueRepositoryInterface $stringRepository */
        $stringRepository = $this->doctrine->getRepository(StringValue::class);

        /** @var \App\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        $events  = count($issue->getEvents());
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $values[$index['Priority']]->getField()->getId()      => 1,
            $values[$index['Description']]->getField()->getId()   => 'Est dolorum omnis accusantium hic veritatis ut.',
            $values[$index['Error']]->getField()->getId()         => true,
            $values[$index['Due date']]->getField()->getId()      => '2017-04-22',
            $values[$index['Commit ID']]->getField()->getId()     => 'fb6c40d246aeeb8934884febcd18d19555fd7725',
            $values[$index['Delta']]->getField()->getId()         => 5182,
            $values[$index['Effort']]->getField()->getId()        => '7:40',
            $values[$index['Test coverage']]->getField()->getId() => '98.52',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        $date = date_create();
        $date->setTimezone(timezone_open($user->getTimezone()));

        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertSame('Test issue', $issue->getSubject());
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Est dolorum omnis accusantium hic veritatis ut.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(1, $values[$index['Error']]->getValue());
        self::assertSame('2017-04-22', $date->setTimestamp($values[$index['Due date']]->getValue())->format('Y-m-d'));
        self::assertSame('fb6c40d246aeeb8934884febcd18d19555fd7725', $stringRepository->find($values[$index['Commit ID']]->getValue())->getValue());
        self::assertSame(5182, $values[$index['Delta']]->getValue());
        self::assertSame(460, $values[$index['Effort']]->getValue());
        self::assertSame('98.52', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        self::assertCount($events + 1, $issue->getEvents());
        self::assertCount($changes + 9, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueEdited, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertNull($event->getParameter());
    }

    public function testSuccessNoChanges(): void
    {
        $this->loginUser('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \App\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \App\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        $events  = count($issue->getEvents());
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand($issue->getId(), 'Development task 1', null);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        self::assertCount($events, $issue->getEvents());
        self::assertCount($changes, $this->doctrine->getRepository(Change::class)->findAll());
    }

    public function testSuccessOnlySubject(): void
    {
        $this->loginUser('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \App\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \App\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        $events  = count($issue->getEvents());
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertSame('Test issue', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        self::assertCount($events + 1, $issue->getEvents());
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueEdited, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertNull($event->getParameter());
    }

    public function testSuccessOnlyRequiredFields(): void
    {
        $this->loginUser('ldoyle@example.com');

        $index = [
            'Commit ID'     => 0,
            'Delta'         => 1,
            'Description'   => 2,
            'Due date'      => 3,
            'Effort'        => 4,
            'Error'         => 5,
            'Priority'      => 6,
            'Test coverage' => 7,
        ];

        /** @var \App\Repository\Contracts\DecimalValueRepositoryInterface $decimalRepository */
        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);

        /** @var \App\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('normal', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(1440, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        $events  = count($issue->getEvents());
        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $command = new UpdateIssueCommand($issue->getId(), null, [
            $values[$index['Priority']]->getField()->getId() => 1,
            $values[$index['Effort']]->getField()->getId()   => '7:40',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertSame('Development task 1', $issue->getSubject());
        self::assertSame('high', $listRepository->find($values[$index['Priority']]->getValue())->getText());
        self::assertSame('Quas sunt reprehenderit vero accusantium.', $textRepository->find($values[$index['Description']]->getValue())->getValue());
        self::assertSame(0, $values[$index['Error']]->getValue());
        self::assertNull($values[$index['Due date']]->getValue());
        self::assertNull($values[$index['Commit ID']]->getValue());
        self::assertSame(5173, $values[$index['Delta']]->getValue());
        self::assertSame(460, $values[$index['Effort']]->getValue());
        self::assertSame('98.49', $decimalRepository->find($values[$index['Test coverage']]->getValue())->getValue());

        self::assertCount($events + 1, $issue->getEvents());
        self::assertCount($changes + 2, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueEdited, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertNull($event->getParameter());
    }

    public function testValidationSubjectLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand($issue->getId(), str_pad('', Issue::MAX_SUBJECT + 1), null);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 250 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationRequiredFields(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $values = $this->repository->getLatestValues($issue);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        $command = new UpdateIssueCommand($issue->getId(), null, [
            $values[0]->getField()->getId() => null,
            $values[1]->getField()->getId() => null,
            $values[2]->getField()->getId() => null,
            $values[3]->getField()->getId() => null,
            $values[4]->getField()->getId() => null,
            $values[5]->getField()->getId() => null,
            $values[6]->getField()->getId() => null,
            $values[7]->getField()->getId() => null,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnListField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => 4,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('The value you selected is not a valid choice.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnTextField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => str_pad('', TextValue::MAX_VALUE + 1, '*'),
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 10000 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnCheckboxField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Error']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => 0,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('\'Error\' should be \'false\' or \'true\'.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnDateField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => '2004-07-08',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('\'Due date\' should be in range from 4/14/17 to 4/28/17.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnStringField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Commit ID']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => str_pad('', 41, '*'),
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 40 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnNumberField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => -1,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('\'Delta\' should be in range from 0 to 1000000000.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnDurationField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => '0:00',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('\'Effort\' should be in range from 0:01 to 160:00.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnDecimalField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Test coverage']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', [
            $field->getId() => '100.01',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('\'Test coverage\' should be in range from 0 to 100.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('ldoyle@example.com');

        $command = new UpdateIssueCommand(self::UNKNOWN_ENTITY_ID, 'Test issue', null);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this issue.');

        $this->loginUser('labshire@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);
    }

    public function testFrozenIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update this issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $issue->getTemplate()->setFrozenTime(1);

        $command = new UpdateIssueCommand($issue->getId(), 'Test issue', null);

        $this->commandBus->handle($command);
    }
}
