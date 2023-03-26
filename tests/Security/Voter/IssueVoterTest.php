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
use App\Entity\Template;
use App\LoginTrait;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use App\Utils\SecondsEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\IssueVoter
 */
final class IssueVoterTest extends TransactionalTestCase
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

        $voter = new IssueVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);
        [$issue5] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [$issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::VIEW_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [IssueVoter::CREATE_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::UPDATE_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::DELETE_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue6, [IssueVoter::CHANGE_STATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::REASSIGN_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue6, [IssueVoter::SUSPEND_ISSUE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issue5, [IssueVoter::RESUME_ISSUE]));
    }

    /**
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByAuthor(): void
    {
        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->loginUser('lucas.oconnell@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue1));
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue2));
    }

    /**
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByResponsible(): void
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->loginUser('nhills@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginUser('jkiehn@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByLocalGroup(): void
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->loginUser('labshire@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginUser('jkiehn@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::isViewGranted
     * @covers ::voteOnAttribute
     */
    public function testViewByGlobalGroup(): void
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginUser('labshire@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginUser('clegros@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$templateA, $templateB, $templateC] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        // Template D doesn't have initial state.
        [$templateD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateA));
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateB));
        self::assertTrue($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateD));

        $this->loginUser('lucas.oconnell@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        /** @var Issue $issueC */
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $assignedToDev3));

        $issueC->getTemplate()->setFrozenTime(1);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        /** @var Issue $issueC */
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $suspended));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $assignedToDev3));

        $issueC->getTemplate()->setFrozenTime(1);

        $this->loginUser('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
    }

    /**
     * @covers ::isChangeStateGranted
     * @covers ::voteOnAttribute
     */
    public function testChangeState(): void
    {
        // Template B is locked, template C is not.
        // Project A is suspended.
        /** @var Issue $issueC */
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]         = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $dependant]         = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $closed]            = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByClient]   = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToSupport] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        // Logging as a manager of all projects (A, B, C, D).
        $this->loginUser('ldoyle@example.com');
        // Project is suspended.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueA));
        // Template is locked.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueB));
        // Everything is OK.
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Issue is suspended.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $suspended));
        // The issue has unclosed dependencies, but it can be moved to an intermediate state, so it's OK.
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $dependant));

        // Logging as a client of projects B and C.
        $this->loginUser('dtillman@example.com');
        // Everything is OK, but the user doesn't have permissions.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Everything is OK, the user is the author.
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $createdByClient));

        // Logging as a support engineer of projects A and C.
        $this->loginUser('cbatz@example.com');
        // Everything is OK, but the user doesn't have permissions.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // Everything is OK, the user is the current responsible.
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $assignedToSupport));

        // Logging as a client of projects A, B, and C.
        $this->loginUser('lucas.oconnell@example.com');
        // Everything is OK, but the user doesn't have permissions.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $issueC));
        // The issue has unclosed dependencies, so it can't be moved to a final state
        // (this is the only state this user can use on the issue).
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $dependant));
        // Everything is OK, but the issue is closed and frozen.
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, $closed));
        // Everything is OK (the issue is not frozen anymore).
        $issueC->getTemplate()->setFrozenTime(null);
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, $closed));
    }

    /**
     * @covers ::isReassignGranted
     * @covers ::voteOnAttribute
     */
    public function testReassign(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        /** @var Issue $issueC */
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $unassigned]     = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $unassigned));

        $this->loginUser('dquigley@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $assignedToDev3));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $assignedToDev3));

        $issueC->suspend(time() + SecondsEnum::OneDay->value);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issueC));
    }

    /**
     * @covers ::isSuspendGranted
     * @covers ::voteOnAttribute
     */
    public function testSuspend(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $suspended]      = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $closed]         = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $suspended));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $closed));

        $this->loginUser('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));
    }

    /**
     * @covers ::isResumeGranted
     * @covers ::voteOnAttribute
     */
    public function testResume(): void
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */ , /* skipping */ , $resumed] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        [/* skipping */ , /* skipping */ , $closed]  = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $createdByDev2 */
        [/* skipping */ , /* skipping */ , $createdByDev2] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $assignedToDev3 */
        [/* skipping */ , /* skipping */ , $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $createdByDev2->suspend(time() + SecondsEnum::OneDay->value);
        $assignedToDev3->suspend(time() + SecondsEnum::OneDay->value);

        $this->loginUser('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $resumed));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $closed));

        $this->loginUser('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));

        $this->loginUser('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));
    }
}
