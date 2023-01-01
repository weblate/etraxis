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

namespace App\Security\Voter;

use App\Entity\Issue;
use App\LoginTrait;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\CommentVoter
 */
final class CommentVoterTest extends TransactionalTestCase
{
    use LoginTrait;

    private AuthorizationCheckerInterface $security;
    private IssueRepositoryInterface      $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $issue));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new CommentVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [CommentVoter::ADD_PUBLIC_COMMENT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [CommentVoter::ADD_PRIVATE_COMMENT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [CommentVoter::READ_PRIVATE_COMMENT]));
    }

    /**
     * @covers ::isAddPublicCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testAddPublicComment(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        /** @var Issue $closed */
        [/* skipping */ , /* skipping */ , $closed] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $issueA));
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $issueC));
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $assignedToDev3));

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $closed));
        $closed->getTemplate()->setFrozenTime(1);
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $closed));
    }

    /**
     * @covers ::isAddPrivateCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testAddPrivateComment(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        /** @var Issue $closed */
        [/* skipping */ , /* skipping */ , $closed] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $issueA));
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $issueC));
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $assignedToDev3));

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $closed));
        $closed->getTemplate()->setFrozenTime(1);
        self::assertFalse($this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $closed));
    }

    /**
     * @covers ::isReadPrivateCommentGranted
     * @covers ::voteOnAttribute
     */
    public function testReadPrivateComment(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issueA));
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $assignedToDev3));
    }
}
