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
 * @coversDefaultClass \App\Security\Voter\FileVoter
 */
final class FileVoterTest extends TransactionalTestCase
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

        $voter = new FileVoter($manager, 10);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [FileVoter::ATTACH_FILE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [FileVoter::DELETE_FILE]));
    }

    /**
     * @covers ::isAttachFileGranted
     * @covers ::voteOnAttribute
     */
    public function testAttachFile(): void
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
        self::assertFalse($this->security->isGranted(FileVoter::ATTACH_FILE, $issueA));
        self::assertFalse($this->security->isGranted(FileVoter::ATTACH_FILE, $issueB));
        self::assertTrue($this->security->isGranted(FileVoter::ATTACH_FILE, $issueC));
        self::assertFalse($this->security->isGranted(FileVoter::ATTACH_FILE, $suspended));

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $voter   = new FileVoter($manager, 0);
        $token   = self::getContainer()->get('security.untracked_token_storage')->getToken();
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issueC, [FileVoter::ATTACH_FILE]));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(FileVoter::ATTACH_FILE, $issueC));
        self::assertTrue($this->security->isGranted(FileVoter::ATTACH_FILE, $createdByDev3));
        self::assertTrue($this->security->isGranted(FileVoter::ATTACH_FILE, $assignedToDev3));

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(FileVoter::ATTACH_FILE, $closed));
        $closed->getTemplate()->setFrozenTime(1);
        self::assertFalse($this->security->isGranted(FileVoter::ATTACH_FILE, $closed));
    }

    /**
     * @covers ::isDeleteFileGranted
     * @covers ::voteOnAttribute
     */
    public function testDeleteFile(): void
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
        self::assertFalse($this->security->isGranted(FileVoter::DELETE_FILE, $issueA));
        self::assertFalse($this->security->isGranted(FileVoter::DELETE_FILE, $issueB));
        self::assertTrue($this->security->isGranted(FileVoter::DELETE_FILE, $issueC));
        self::assertFalse($this->security->isGranted(FileVoter::DELETE_FILE, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(FileVoter::DELETE_FILE, $issueC));
        self::assertTrue($this->security->isGranted(FileVoter::DELETE_FILE, $createdByDev3));
        self::assertTrue($this->security->isGranted(FileVoter::DELETE_FILE, $assignedToDev3));

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(FileVoter::DELETE_FILE, $closed));
        $closed->getTemplate()->setFrozenTime(1);
        self::assertFalse($this->security->isGranted(FileVoter::DELETE_FILE, $closed));
    }
}
