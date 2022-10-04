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
//use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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
        $voter = new UserVoter();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $nhills = $this->repository->findOneByEmail('nhills@example.com');

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::SET_PASSWORD]));
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
}
