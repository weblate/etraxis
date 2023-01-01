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

use App\Entity\Issue;
use App\Entity\User;
use App\Entity\Watcher;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\WatcherRepository
 */
final class WatcherRepositoryTest extends TransactionalTestCase
{
    private Contracts\WatcherRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Watcher::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $watchers = $this->repository->findAllByIssue($issue);

        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        $actual = array_map(fn (User $user) => $user->getEmail(), $watchers);
        sort($actual);

        self::assertCount(2, $watchers);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueEmpty(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $watchers = $this->repository->findAllByIssue($issue);

        self::assertEmpty($watchers);
    }
}
