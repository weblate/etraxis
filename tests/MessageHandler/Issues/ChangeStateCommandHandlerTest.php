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

namespace App\MessageHandler\Issues;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Issues\ChangeStateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Issues\ChangeStateCommandHandler::__invoke
 */
final class ChangeStateCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccessInitialToIntermediate(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertNotSame($assignee, $issue->getResponsible());
        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertCount(3, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('New feature', $values[1]->getField()->getName());
        self::assertSame('Priority', $values[2]->getField()->getName());

        $events = count($issue->getEvents());

        $date_value = date('Y-m-d');

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), [
            $field->getId() => $date_value,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertSame($assignee, $issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('Due date', $values[1]->getField()->getName());
        self::assertSame('New feature', $values[2]->getField()->getName());
        self::assertSame('Priority', $values[3]->getField()->getName());

        $date = date_create();
        $date->setTimezone(timezone_open('UTC'));

        self::assertSame($date_value, $date->setTimestamp($values[1]->getValue())->format('Y-m-d'));

        self::assertCount($events + 2, $issue->getEvents());

        /** @var \App\Entity\Event[] $events */
        $events = $issue->getEvents()->toArray();
        $event2 = end($events);
        $event1 = prev($events);

        self::assertSame(EventTypeEnum::StateChanged, $event1->getType());
        self::assertSame($issue, $event1->getIssue());
        self::assertSame($user, $event1->getUser());
        self::assertLessThanOrEqual(2, time() - $event1->getCreatedAt());
        self::assertSame($state->getName(), $event1->getParameter());

        self::assertSame(EventTypeEnum::IssueAssigned, $event2->getType());
        self::assertSame($issue, $event2->getIssue());
        self::assertSame($user, $event2->getUser());
        self::assertLessThanOrEqual(2, time() - $event2->getCreatedAt());
        self::assertSame($assignee->getFullname(), $event2->getParameter());
    }

    public function testSuccessIntermediateToFinal(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [/* skipping */ , /* skipping */ , $duplicate] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertNotNull($issue->getResponsible());
        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('Due date', $values[1]->getField()->getName());
        self::assertSame('New feature', $values[2]->getField()->getName());
        self::assertSame('Priority', $values[3]->getField()->getName());

        $events = count($issue->getEvents());

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field->getId() => $duplicate->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertNull($issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertCount(5, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('Due date', $values[1]->getField()->getName());
        self::assertSame('Issue ID', $values[2]->getField()->getName());
        self::assertSame('New feature', $values[3]->getField()->getName());
        self::assertSame('Priority', $values[4]->getField()->getName());

        self::assertSame($duplicate->getId(), $values[2]->getValue());

        self::assertCount($events + 1, $issue->getEvents());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueClosed, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertSame($state->getName(), $event->getParameter());
    }

    public function testSuccessFinalToInitial(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $state, 'name' => 'New feature']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        self::assertNotNull($issue);
        self::assertNotNull($issue->getClosedAt());
        self::assertCount(8, $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user));

        $events = count($issue->getEvents());

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field1->getId() => 2,
            $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
            $field3->getId() => true,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertNull($issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertNull($issue->getClosedAt());
        self::assertCount(11, $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user));
        self::assertCount($events + 1, $issue->getEvents());

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertSame(EventTypeEnum::IssueReopened, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertSame($state->getName(), $event->getParameter());
    }

    public function testSuccessOnlyResponsible(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertNotSame($assignee, $issue->getResponsible());
        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertCount(3, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('New feature', $values[1]->getField()->getName());
        self::assertSame('Priority', $values[2]->getField()->getName());

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertSame($assignee, $issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('Due date', $values[1]->getField()->getName());
        self::assertSame('New feature', $values[2]->getField()->getName());
        self::assertSame('Priority', $values[3]->getField()->getName());

        $date = date_create();
        $date->setTimezone(timezone_open('UTC'));

        self::assertNull($values[1]->getValue());
    }

    public function testSuccessOnlyRequiredFields(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field1 */
        [/* skipping */ , /* skipping */ , $field1] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var Field $field2 */
        [/* skipping */ , /* skipping */ , $field2] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertGreaterThan(2, time() - $issue->getChangedAt());
        self::assertCount(4, $values);
        self::assertSame('Description', $values[0]->getField()->getName());
        self::assertSame('Due date', $values[1]->getField()->getName());
        self::assertSame('New feature', $values[2]->getField()->getName());
        self::assertSame('Priority', $values[3]->getField()->getName());

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field1->getId() => 216,
            $field2->getId() => '1:25',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        $values = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);

        usort($values, fn (FieldValue $value1, FieldValue $value2) => strcmp($value1->getField()->getName(), $value2->getField()->getName()));

        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
        self::assertCount(8, $values);
        self::assertSame('Commit ID', $values[0]->getField()->getName());
        self::assertSame('Delta', $values[1]->getField()->getName());
        self::assertSame('Description', $values[2]->getField()->getName());
        self::assertSame('Due date', $values[3]->getField()->getName());
        self::assertSame('Effort', $values[4]->getField()->getName());
        self::assertSame('New feature', $values[5]->getField()->getName());
        self::assertSame('Priority', $values[6]->getField()->getName());
        self::assertSame('Test coverage', $values[7]->getField()->getName());

        self::assertNull($values[0]->getValue());
        self::assertSame(216, $values[1]->getValue());
        self::assertSame(85, $values[4]->getValue());
        self::assertNull($values[7]->getValue());
    }

    public function testFailedWithResponsible(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Responsible is required.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, null);

        $this->commandBus->handle($command);
    }

    public function testValidationRequiredFields(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('tmarquardt@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field1 */
        [/* skipping */ , /* skipping */ , $field1] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var Field $field2 */
        [/* skipping */ , /* skipping */ , $field2] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field1->getId() => null,
            $field2->getId() => null,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationOnIssueField(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field->getId() => 0,
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should be greater than 0.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new ChangeStateCommand(self::UNKNOWN_ENTITY_ID, $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }

    public function testUnknownState(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), self::UNKNOWN_ENTITY_ID, null, null);

        $this->commandBus->handle($command);
    }

    public function testUnknownResponsible(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown responsible.');

        $this->loginUser('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), self::UNKNOWN_ENTITY_ID, null);

        $this->commandBus->handle($command);
    }

    public function testResponsibleDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The issue cannot be assigned to specified user.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }

    public function testAccessDeniedByUser(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state.');

        $this->loginUser('labshire@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }

    public function testAccessDeniedByState(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state to specified one.');

        $this->loginUser('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */ , /* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [/* skipping */ , /* skipping */ , $duplicate] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), null, [
            $field->getId() => $duplicate->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to change the current state.');

        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new ChangeStateCommand($issue->getId(), $state->getId(), $assignee->getId(), null);

        $this->commandBus->handle($command);
    }
}
