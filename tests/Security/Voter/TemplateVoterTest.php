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
use App\Entity\Template;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\TemplateVoter
 */
final class TemplateVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private TemplateRepositoryInterface   $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $template));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new TemplateVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $project, [TemplateVoter::CREATE_TEMPLATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UPDATE_TEMPLATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::DELETE_TEMPLATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::LOCK_TEMPLATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UNLOCK_TEMPLATE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::GET_TEMPLATE_PERMISSIONS]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::SET_TEMPLATE_PERMISSIONS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        [$templateA] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [$templateD] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));
    }

    /**
     * @covers ::isLockGranted
     * @covers ::voteOnAttribute
     */
    public function testLock(): void
    {
        [$templateA, /* skipping */ , $templateC] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));
    }

    /**
     * @covers ::isUnlockGranted
     * @covers ::voteOnAttribute
     */
    public function testUnlock(): void
    {
        [$templateA, /* skipping */ , $templateC] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));
    }

    /**
     * @covers ::isGetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetPermissions(): void
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::GET_TEMPLATE_PERMISSIONS, $template));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::GET_TEMPLATE_PERMISSIONS, $template));
    }

    /**
     * @covers ::isSetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPermissions(): void
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::SET_TEMPLATE_PERMISSIONS, $template));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::SET_TEMPLATE_PERMISSIONS, $template));
    }
}
