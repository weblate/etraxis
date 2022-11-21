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

use App\Entity\Dependency;
use App\Entity\Issue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\DependencyRepository
 */
final class DependencyRepositoryTest extends TransactionalTestCase
{
    private Contracts\DependencyRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Dependency::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $dependencies = $this->repository->findAllByIssue($issue);

        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Development task 8'],
        ];

        $actual = array_map(fn (Issue $issue) => [$issue->getProject()->getName(), $issue->getSubject()], $dependencies);

        self::assertCount(2, $dependencies);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueEmpty(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $dependencies = $this->repository->findAllByIssue($issue);

        self::assertEmpty($dependencies);
    }
}
