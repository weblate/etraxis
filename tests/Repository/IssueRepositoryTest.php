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

namespace App\Repository;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\SecondsEnum;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\StringValue;
use App\Entity\TextValue;
use App\Entity\User;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\IssueRepository
 */
final class IssueRepositoryTest extends TransactionalTestCase
{
    private Contracts\IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::getAllValues
     */
    public function testGetAllValues(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $stringValue = $this->doctrine->getRepository(StringValue::class)->get('940059027173b8e8e1e3e874681f012f1f3bcf1d');
        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos soluta excepturi omnis.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->getAllValues($issue);

        $expected = [
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['Error', 1],
            ['Due date', $issue->getCreatedAt() + 7 * SecondsEnum::OneDay->value],
            ['Commit ID', $stringValue->getId()],
            ['Delta', 1],
            ['Effort', 80],
            ['Test coverage', null],
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 5 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(12, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getLatestValues
     */
    public function testGetLatestValues(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $stringValue = $this->doctrine->getRepository(StringValue::class)->get('940059027173b8e8e1e3e874681f012f1f3bcf1d');
        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos soluta excepturi omnis.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->getLatestValues($issue);

        $expected = [
            ['Commit ID', $stringValue->getId()],
            ['Delta', 1],
            ['Effort', 80],
            ['Test coverage', null],
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 5 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(8, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::hasOpenedDependencies
     */
    public function testHasOpenedDependencies(): void
    {
        /** @var Issue $issue2 This issue has one closed dependency. */
        [$issue2] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $issue4 This issue has no dependencies at all. */
        [$issue4] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $issue6 This issue has two dependencies, and one of them is still active. */
        [$issue6] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        self::assertFalse($this->repository->hasOpenedDependencies($issue4));
        self::assertFalse($this->repository->hasOpenedDependencies($issue2));
        self::assertTrue($this->repository->hasOpenedDependencies($issue6));
    }

    /**
     * @covers ::getTransitionsByUser
     */
    public function testGetTransitionsByUser(): void
    {
        /** @var Issue $issue1 */
        [$issue1] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue4 */
        [/* skipping */ , /* skipping */ , $issue4] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $issue6 */
        [/* skipping */ , /* skipping */ , $issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var User $manager Manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $support Support engineer */
        $support = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $author4 A client (the author of the issue 4) */
        $author4 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $author6 A client (the author of the issue 6) */
        $author6 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $states = $this->repository->getTransitionsByUser($issue1, $manager);
        self::assertCount(1, $states);
        self::assertSame('New', $states[0]->getName());

        $states = $this->repository->getTransitionsByUser($issue4, $manager);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->getName());

        $states = $this->repository->getTransitionsByUser($issue4, $support);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->getName());

        $states = $this->repository->getTransitionsByUser($issue4, $author4);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->getName());

        $states = $this->repository->getTransitionsByUser($issue4, $author6);
        self::assertCount(0, $states);

        $states = $this->repository->getTransitionsByUser($issue6, $manager);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->getName());

        $states = $this->repository->getTransitionsByUser($issue6, $support);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->getName());

        // Author should be able to move the issue to a final state,
        // but the issue has unclosed dependencies.
        $states = $this->repository->getTransitionsByUser($issue6, $author6);
        self::assertCount(0, $states);
    }

    /**
     * @covers ::getResponsiblesByState
     */
    public function testGetResponsiblesByState(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $users = $this->repository->getResponsiblesByState($issue->getState());
        self::assertCount(4, $users);

        $expected = [
            'Carter Batz',
            'Kailyn Bahringer',
            'Tony Buckridge',
            'Tracy Marquardt',
        ];

        $actual = array_map(fn (User $user) => $user->getFullname(), $users);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceByManagerAB(): void
    {
        $expected = [
            ['Distinctio',  'Development task 1'],
            ['Distinctio',  'Development task 2'],
            ['Distinctio',  'Development task 3'],
            ['Distinctio',  'Development task 4'],
            ['Distinctio',  'Development task 5'],
            ['Distinctio',  'Development task 6'],
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio',  'Development task 7'],
            ['Distinctio',  'Development task 8'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dorcas.ernser@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $reduced);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceByDeveloperB(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'amarvin@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $reduced);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceBySupportB(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'vparker@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $reduced);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceByClientB(): void
    {
        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'aschinner@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        self::assertCount(0, $reduced);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceByAuthor(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 6'],
        ];

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $reduced);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::reduceByUser
     */
    public function testReduceByResponsible(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Development task 8'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);
        $issues  = $this->repository->findBy([], ['id' => 'ASC']);

        $reduced = $this->repository->reduceByUser($manager, $issues);

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $reduced);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssueSuccess(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $count = count($issue->getEvents());

        self::assertNotSame($assignee, $issue->getResponsible());

        $result = $this->repository->assignIssue($manager, $issue, $assignee);

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertTrue($result);
        self::assertSame($assignee, $issue->getResponsible());
        self::assertCount($count + 1, $issue->getEvents());
        self::assertSame(EventTypeEnum::IssueAssigned, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($manager, $event->getUser());
        self::assertSame($assignee->getFullname(), $event->getParameter());
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssueWrongResponsible(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $count = count($issue->getEvents());

        self::assertNotSame($assignee, $issue->getResponsible());

        $result = $this->repository->assignIssue($manager, $issue, $assignee);

        self::assertFalse($result);
        self::assertNotSame($assignee, $issue->getResponsible());
        self::assertCount($count, $issue->getEvents());
    }

    /**
     * @covers ::reassignIssue
     */
    public function testReassignIssueSuccess(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $count = count($issue->getEvents());

        self::assertNotSame($assignee, $issue->getResponsible());

        $result = $this->repository->reassignIssue($manager, $issue, $assignee);

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->last();

        self::assertTrue($result);
        self::assertSame($assignee, $issue->getResponsible());
        self::assertCount($count + 1, $issue->getEvents());
        self::assertSame(EventTypeEnum::IssueReassigned, $event->getType());
        self::assertSame($issue, $event->getIssue());
        self::assertSame($manager, $event->getUser());
        self::assertSame($assignee->getFullname(), $event->getParameter());
    }

    /**
     * @covers ::reassignIssue
     */
    public function testReassignIssueNotAssigned(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $count = count($issue->getEvents());

        self::assertNull($issue->getResponsible());

        $result = $this->repository->reassignIssue($manager, $issue, $assignee);

        self::assertFalse($result);
        self::assertNull($issue->getResponsible());
        self::assertCount($count, $issue->getEvents());
    }

    /**
     * @covers ::reassignIssue
     */
    public function testReassignIssueWrongResponsible(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $count = count($issue->getEvents());

        self::assertNotNull($issue->getResponsible());
        self::assertNotSame($assignee, $issue->getResponsible());

        $result = $this->repository->reassignIssue($manager, $issue, $assignee);

        self::assertFalse($result);
        self::assertNotSame($assignee, $issue->getResponsible());
        self::assertCount($count, $issue->getEvents());
    }
}
