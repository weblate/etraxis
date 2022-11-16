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

use App\Entity\Group;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\GroupVoter
 */
final class GroupVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private GroupRepositoryInterface      $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $group));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        $voter = new GroupVoter();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [GroupVoter::CREATE_GROUP]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::UPDATE_GROUP]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::DELETE_GROUP]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::MANAGE_GROUP_MEMBERS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::CREATE_GROUP));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::CREATE_GROUP));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));
    }

    /**
     * @covers ::isManageMembershipGranted
     * @covers ::voteOnAttribute
     */
    public function testManageMembership(): void
    {
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::MANAGE_GROUP_MEMBERS, $group));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::MANAGE_GROUP_MEMBERS, $group));
    }
}
