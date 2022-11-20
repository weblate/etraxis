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

use App\Entity\Issue;
use App\Entity\RelatedIssue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\RelatedIssueRepository
 */
final class RelatedIssueRepositoryTest extends TransactionalTestCase
{
    private Contracts\RelatedIssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(RelatedIssue::class);
    }

    /**
     * @covers ::getRelatedIssues
     */
    public function testGetRelatedIssues(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $relatedIssues = $this->repository->getRelatedIssues($issue);

        $expected = [
            ['Distinctio', 'Development task 2'],
        ];

        $actual = array_map(fn (Issue $issue) => [$issue->getProject()->getName(), $issue->getSubject()], $relatedIssues);

        self::assertCount(1, $relatedIssues);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getRelatedIssues
     */
    public function testGetRelatedIssuesEmpty(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 7'], ['id' => 'ASC']);

        $relatedIssues = $this->repository->getRelatedIssues($issue);

        self::assertEmpty($relatedIssues);
    }
}
