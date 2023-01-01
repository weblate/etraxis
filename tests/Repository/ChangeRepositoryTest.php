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

namespace App\Repository;

use App\Entity\Change;
use App\Entity\Enums\SecondsEnum;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\StringValue;
use App\Entity\TextValue;
use App\Entity\User;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\ChangeRepository
 */
final class ChangeRepositoryTest extends TransactionalTestCase
{
    private Contracts\ChangeRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Change::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithSubject(): void
    {
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $oldSubject = $this->doctrine->getRepository(StringValue::class)->get('Task 1');
        $newSubject = $this->doctrine->getRepository(StringValue::class)->get('Development task 1');

        [$oldListItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'low'], ['id' => 'ASC']);
        [$newListItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'normal'], ['id' => 'ASC']);

        $changes = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            [null, $oldSubject->getId(), $newSubject->getId()],
            ['Priority', $oldListItem->getId(), $newListItem->getId()],
        ];

        $actual = array_map(fn (Change $change) => [
            $change->getField()?->getName(),
            $change->getOldValue(),
            $change->getNewValue(),
        ], $changes);

        self::assertCount(2, $changes);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueMultipleEvents(): void
    {
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        [$oldListItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'low'], ['id' => 'ASC']);
        [$newListItem] = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $oldDescription = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos.');
        $newDescription = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos soluta excepturi omnis.');

        $changes = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            ['Priority', $oldListItem->getId(), $newListItem->getId()],
            ['Description', $oldDescription->getId(), $newDescription->getId()],
            ['Due date', $issue->getCreatedAt() + SecondsEnum::OneDay->value * 14, $issue->getCreatedAt() + SecondsEnum::OneDay->value * 7],
        ];

        $actual = array_map(fn (Change $change) => [
            $change->getField()?->getName(),
            $change->getOldValue(),
            $change->getNewValue(),
        ], $changes);

        self::assertCount(3, $changes);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueEmpty(): void
    {
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $changes = $this->repository->findAllByIssue($issue, $user);

        $expected = [];

        $actual = array_map(fn (Change $change) => [
            $change->getField()?->getName(),
            $change->getOldValue(),
            $change->getNewValue(),
        ], $changes);

        self::assertEmpty($changes);
        self::assertSame($expected, $actual);
    }
}
