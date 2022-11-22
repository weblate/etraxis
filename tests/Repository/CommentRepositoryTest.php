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

use App\Entity\Comment;
use App\Entity\Issue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\CommentRepository
 */
final class CommentRepositoryTest extends TransactionalTestCase
{
    private Contracts\CommentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Comment::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithPrivateComments(): void
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Ut ipsum explicabo iste sequi dignissimos.',
            'Natus excepturi est eaque nostrum non.',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $comments = $this->repository->findAllByIssue($issue, false);

        self::assertCount(3, $comments);

        $actual = array_map(fn (Comment $comment) => mb_substr($comment->getBody(), 0, mb_strpos($comment->getBody(), '.') + 1), $comments);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueHidePrivateComments(): void
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Natus excepturi est eaque nostrum non.',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $comments = $this->repository->findAllByIssue($issue, true);

        self::assertCount(2, $comments);

        $actual = array_map(fn (Comment $comment) => mb_substr($comment->getBody(), 0, mb_strpos($comment->getBody(), '.') + 1), $comments);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }
}
