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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Issue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\EventRepository
 */
final class EventRepositoryTest extends TransactionalTestCase
{
    private Contracts\EventRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Event::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithPrivateComments(): void
    {
        $expected = [
            [EventTypeEnum::IssueCreated,      'Dorcas Ernser'],
            [EventTypeEnum::StateChanged,      'Leland Doyle'],
            [EventTypeEnum::IssueAssigned,     'Leland Doyle'],
            [EventTypeEnum::FileAttached,      'Leland Doyle'],
            [EventTypeEnum::FileAttached,      'Leland Doyle'],
            [EventTypeEnum::PublicComment,     'Leland Doyle'],
            [EventTypeEnum::IssueClosed,       'Dennis Quigley'],
            [EventTypeEnum::IssueReopened,     'Dorcas Ernser'],
            [EventTypeEnum::IssueEdited,       'Dorcas Ernser'],
            [EventTypeEnum::StateChanged,      'Dorcas Ernser'],
            [EventTypeEnum::IssueAssigned,     'Dorcas Ernser'],
            [EventTypeEnum::IssueEdited,       'Dorcas Ernser'],
            [EventTypeEnum::FileDeleted,       'Dorcas Ernser'],
            [EventTypeEnum::PrivateComment,    'Dorcas Ernser'],
            [EventTypeEnum::RelatedIssueAdded, 'Dorcas Ernser'],
            [EventTypeEnum::FileAttached,      'Dennis Quigley'],
            [EventTypeEnum::PublicComment,     'Dennis Quigley'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $events = $this->repository->findAllByIssue($issue, false);

        $actual = array_map(fn (Event $event) => [
            $event->getType(),
            $event->getUser()->getFullname(),
        ], $events);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueHidePrivateComments(): void
    {
        $expected = [
            [EventTypeEnum::IssueCreated,      'Dorcas Ernser'],
            [EventTypeEnum::StateChanged,      'Leland Doyle'],
            [EventTypeEnum::IssueAssigned,     'Leland Doyle'],
            [EventTypeEnum::FileAttached,      'Leland Doyle'],
            [EventTypeEnum::FileAttached,      'Leland Doyle'],
            [EventTypeEnum::PublicComment,     'Leland Doyle'],
            [EventTypeEnum::IssueClosed,       'Dennis Quigley'],
            [EventTypeEnum::IssueReopened,     'Dorcas Ernser'],
            [EventTypeEnum::IssueEdited,       'Dorcas Ernser'],
            [EventTypeEnum::StateChanged,      'Dorcas Ernser'],
            [EventTypeEnum::IssueAssigned,     'Dorcas Ernser'],
            [EventTypeEnum::IssueEdited,       'Dorcas Ernser'],
            [EventTypeEnum::FileDeleted,       'Dorcas Ernser'],
            [EventTypeEnum::RelatedIssueAdded, 'Dorcas Ernser'],
            [EventTypeEnum::FileAttached,      'Dennis Quigley'],
            [EventTypeEnum::PublicComment,     'Dennis Quigley'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $events = $this->repository->findAllByIssue($issue, true);

        $actual = array_map(fn (Event $event) => [
            $event->getType(),
            $event->getUser()->getFullname(),
        ], $events);

        self::assertSame($expected, $actual);
    }
}
