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

use App\Entity\Project;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\ProjectVoter
 */
final class ProjectVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private ?AuthorizationCheckerInterface              $security;
    private ObjectRepository|ProjectRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $project));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ProjectVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [ProjectVoter::CREATE_PROJECT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::UPDATE_PROJECT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::DELETE_PROJECT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::SUSPEND_PROJECT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::RESUME_PROJECT]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::CREATE_PROJECT));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::CREATE_PROJECT));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectD = $this->repository->findOneBy(['name' => 'Presto']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        self::assertTrue($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));
    }

    /**
     * @covers ::isSuspendGranted
     * @covers ::voteOnAttribute
     */
    public function testSuspend(): void
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        self::assertTrue($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));
    }

    /**
     * @covers ::isResumeGranted
     * @covers ::voteOnAttribute
     */
    public function testResume(): void
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));
    }
}
