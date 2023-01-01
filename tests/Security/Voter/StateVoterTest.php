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

use App\Entity\State;
use App\Entity\Template;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\StateVoter
 */
final class StateVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private StateRepositoryInterface      $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $state));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new StateVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [StateVoter::CREATE_STATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::UPDATE_STATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::DELETE_STATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_INITIAL_STATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::GET_STATE_TRANSITIONS]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_STATE_TRANSITIONS]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::GET_RESPONSIBLE_GROUPS]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_RESPONSIBLE_GROUPS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        /** @var \App\Repository\Contracts\TemplateRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [/* skipping */ , $templateB, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        [/* skipping */ , $stateB, $stateC, $stateD] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        self::assertTrue($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));
    }

    /**
     * @covers ::isSetInitialGranted
     * @covers ::voteOnAttribute
     */
    public function testSetInitial(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::SET_INITIAL_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL_STATE, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL_STATE, $stateC));
    }

    /**
     * @covers ::isGetTransitionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetTransitions(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateB));
        self::assertTrue($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateC));

        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_STATE_TRANSITIONS, $stateC));
    }

    /**
     * @covers ::isSetTransitionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetTransitions(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateB));
        self::assertTrue($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateC));

        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $stateC));
    }

    /**
     * @covers ::isGetResponsibleGroupsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetResponsibleGroups(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        self::assertTrue($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::GET_RESPONSIBLE_GROUPS, $stateC));
    }

    /**
     * @covers ::isSetResponsibleGroupsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetResponsibleGroups(): void
    {
        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        self::assertTrue($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        [/* skipping */ , $stateB, $stateC] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $stateC));
    }
}
