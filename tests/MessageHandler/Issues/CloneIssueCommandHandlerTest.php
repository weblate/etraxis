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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\Group;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\State;
use App\Entity\StateResponsibleGroup;
use App\Entity\TextValue;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Issues\CloneIssueCommand;
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
 * @covers \App\MessageHandler\Issues\CloneIssueCommandHandler::__invoke
 */
final class CloneIssueCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccessNoResponsible(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'New feature']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
            $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
            $field3->getId() => true,
        ]);

        $result = $this->commandBus->handleWithResult($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($issue, $result);

        self::assertSame('Test issue', $issue->getSubject());
        self::assertSame($origin->getTemplate()->getInitialState(), $issue->getState());
        self::assertSame('nhills@example.com', $issue->getAuthor()->getEmail());
        self::assertNull($issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getCreatedAt());
        self::assertLessThanOrEqual(2, $issue->getChangedAt() - $issue->getCreatedAt());
        self::assertNull($issue->getClosedAt());

        self::assertCount(1, $issue->getEvents());

        $event = $issue->getEvents()[0];

        self::assertSame(EventTypeEnum::IssueCreated, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($issue->getAuthor(), $event->getUser());
        self::assertLessThanOrEqual(2, $event->getCreatedAt() - $issue->getCreatedAt());
        self::assertSame($issue->getState()->getName(), $event->getParameter());

        /** @var FieldValue[] $values */
        $values = array_filter(
            $this->repository->getAllValues($issue, null),
            fn (FieldValue $value) => $value->getField()->getState() === $origin->getTemplate()->getInitialState()
        );

        self::assertCount(3, $values);

        self::assertSame($field1, $values[0]->getField());
        self::assertSame($field2, $values[1]->getField());
        self::assertSame($field3, $values[2]->getField());

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);
        $listValue      = $listRepository->findOneByValue($field1, 2);

        /** @var \App\Repository\Contracts\TextValueRepositoryInterface $textRepository */
        $textRepository = $this->doctrine->getRepository(TextValue::class);
        $textValue      = $textRepository->get('Est dolorum omnis accusantium hic veritatis ut.');

        self::assertSame($listValue->getId(), $values[0]->getValue());
        self::assertSame($textValue->getId(), $values[1]->getValue());
        self::assertSame(1, $values[2]->getValue());
    }

    public function testSuccessWithResponsible(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */ , /* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $responsibleGroup = new StateResponsibleGroup($state, $group);

        $this->doctrine->getManager()->persist($responsibleGroup);
        $this->doctrine->getManager()->flush();

        $state->setResponsible(StateResponsibleEnum::Assign);

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'New feature']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', $user->getId(), [
            $field1->getId() => 2,
            $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
            $field3->getId() => true,
        ]);

        $result = $this->commandBus->handleWithResult($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($issue, $result);

        self::assertSame('Test issue', $issue->getSubject());
        self::assertSame($origin->getTemplate()->getInitialState(), $issue->getState());
        self::assertSame('nhills@example.com', $issue->getAuthor()->getEmail());
        self::assertSame('dquigley@example.com', $issue->getResponsible()->getEmail());
        self::assertLessThanOrEqual(2, time() - $issue->getCreatedAt());
        self::assertLessThanOrEqual(2, $issue->getChangedAt() - $issue->getCreatedAt());
        self::assertNull($issue->getClosedAt());

        self::assertCount(2, $issue->getEvents());

        $event1 = $issue->getEvents()[0];
        $event2 = $issue->getEvents()[1];

        self::assertSame(EventTypeEnum::IssueCreated, $event1->getType());
        self::assertSame($issue, $event1->getIssue());
        self::assertSame($issue->getAuthor(), $event1->getUser());
        self::assertSame($issue->getCreatedAt(), $event1->getCreatedAt());
        self::assertSame($issue->getState()->getName(), $event1->getParameter());

        self::assertSame(EventTypeEnum::IssueAssigned, $event2->getType());
        self::assertSame($issue, $event2->getIssue());
        self::assertSame($issue->getAuthor(), $event2->getUser());
        self::assertLessThanOrEqual(2, $event2->getCreatedAt() - $issue->getCreatedAt());
        self::assertSame($issue->getResponsible()->getFullname(), $event2->getParameter());
    }

    public function testFailedWithResponsible(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Responsible is required.');

        $this->loginUser('nhills@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */ , /* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $responsibleGroup = new StateResponsibleGroup($state, $group);

        $this->doctrine->getManager()->persist($responsibleGroup);
        $this->doctrine->getManager()->flush();

        $state->setResponsible(StateResponsibleEnum::Assign);

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'New feature']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
            $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
            $field3->getId() => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuccessOnlyRequiredFields(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
        ]);

        $result = $this->commandBus->handleWithResult($command);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertInstanceOf(Issue::class, $issue);
        self::assertSame($issue, $result);

        self::assertSame('Test issue', $issue->getSubject());
        self::assertSame($origin->getTemplate()->getInitialState(), $issue->getState());
        self::assertSame('nhills@example.com', $issue->getAuthor()->getEmail());
        self::assertNull($issue->getResponsible());
        self::assertLessThanOrEqual(2, time() - $issue->getCreatedAt());
        self::assertLessThanOrEqual(2, $issue->getChangedAt() - $issue->getCreatedAt());
        self::assertNull($issue->getClosedAt());

        self::assertCount(1, $issue->getEvents());

        $event = $issue->getEvents()[0];

        self::assertSame(EventTypeEnum::IssueCreated, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($issue->getAuthor(), $event->getUser());
        self::assertLessThanOrEqual(2, $event->getCreatedAt() - $issue->getCreatedAt());
        self::assertSame($issue->getState()->getName(), $event->getParameter());

        /** @var FieldValue[] $values */
        $values = array_filter(
            $this->repository->getAllValues($issue, null),
            fn (FieldValue $value) => $value->getField()->getState() === $origin->getTemplate()->getInitialState()
        );

        self::assertCount(3, $values);

        self::assertSame($field1, $values[0]->getField());

        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $listRepository */
        $listRepository = $this->doctrine->getRepository(ListItem::class);
        $listValue      = $listRepository->findOneByValue($field1, 2);

        self::assertSame($listValue->getId(), $values[0]->getValue());
        self::assertNull($values[1]->getValue());
        self::assertNull($values[2]->getValue());
    }

    public function testValidationSubjectLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand($origin->getId(), str_pad('', Issue::MAX_SUBJECT + 1), null, [
            $field1->getId() => 2,
        ]);

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

        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, null);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownIssue(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand(self::UNKNOWN_ENTITY_ID, 'Test issue', null, [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownResponsible(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown responsible.');

        $this->loginUser('nhills@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $state->setResponsible(StateResponsibleEnum::Assign);

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', self::UNKNOWN_ENTITY_ID, [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new issue.');

        $this->loginUser('labshire@example.com');

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }

    public function testResponsibleDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The issue cannot be assigned to specified user.');

        $this->loginUser('nhills@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $state->setResponsible(StateResponsibleEnum::Assign);

        /** @var Issue $origin */
        [/* skipping */ , /* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        /** @var Issue $issue */
        $issue = $this->repository->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', $user->getId(), [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new issue.');

        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [$origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new issue.');

        $this->loginUser('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */ , $origin] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $origin->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $command = new CloneIssueCommand($origin->getId(), 'Test issue', null, [
            $field1->getId() => 2,
        ]);

        $this->commandBus->handle($command);
    }
}
