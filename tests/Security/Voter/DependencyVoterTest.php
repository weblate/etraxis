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
 * @coversDefaultClass \App\Security\Voter\DependencyVoter
 */
final class DependencyVoterTest extends TransactionalTestCase
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

        $voter = new DependencyVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [DependencyVoter::ADD_DEPENDENCY]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue, [DependencyVoter::REMOVE_DEPENDENCY]));
    }

    /**
     * @covers ::isAddDependencyGranted
     * @covers ::voteOnAttribute
     */
    public function testAddDependency(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $closed]         = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issueA));
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issueB));
        self::assertTrue($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $suspended));
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $closed));

        $this->loginUser('dquigley@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issueC));
        self::assertTrue($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $createdByDev2));
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $assignedToDev3));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $createdByDev2));
        self::assertTrue($this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $assignedToDev3));
    }

    /**
     * @covers ::isRemoveDependencyGranted
     * @covers ::voteOnAttribute
     */
    public function testRemoveDependency(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $closed]         = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issueA));
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issueB));
        self::assertTrue($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $suspended));
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $closed));

        $this->loginUser('dquigley@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertTrue($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $createdByDev2));
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $assignedToDev3));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $createdByDev2));
        self::assertTrue($this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $assignedToDev3));
    }
}
