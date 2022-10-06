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

use App\Entity\User;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\UserVoter
 */
final class UserVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private ?AuthorizationCheckerInterface           $security;
    private ObjectRepository|UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $nhills));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new UserVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $nhills = $this->repository->findOneByEmail('nhills@example.com');

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [UserVoter::CREATE_USER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UPDATE_USER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DELETE_USER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DISABLE_USER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::ENABLE_USER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::SET_PASSWORD]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::MANAGE_USER_GROUPS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::CREATE_USER));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::CREATE_USER));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        $amarvin = $this->repository->findOneByEmail('amarvin@example.com');
        $nhills  = $this->repository->findOneByEmail('nhills@example.com');
        $admin   = $this->repository->findOneByEmail('admin@example.com');

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $admin));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $amarvin));
    }

    /**
     * @covers ::isDisableGranted
     * @covers ::voteOnAttribute
     */
    public function testDisable(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');
        $admin  = $this->repository->findOneByEmail('admin@example.com');

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $admin));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
    }

    /**
     * @covers ::isEnableGranted
     * @covers ::voteOnAttribute
     */
    public function testEnable(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');
        $admin  = $this->repository->findOneByEmail('admin@example.com');

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $admin));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
    }

    /**
     * @covers ::isSetPasswordGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPassword(): void
    {
        $nhills   = $this->repository->findOneByEmail('nhills@example.com');
        $einstein = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginUser('nhills@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginUser('einstein@ldap.forumsys.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));
    }

    /**
     * @covers ::isManageMembershipGranted
     * @covers ::voteOnAttribute
     */
    public function testManageMembership(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::MANAGE_USER_GROUPS, $nhills));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::MANAGE_USER_GROUPS, $nhills));
    }
}
