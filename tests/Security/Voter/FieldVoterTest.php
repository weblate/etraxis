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

use App\Entity\Field;
use App\Entity\State;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\FieldVoter
 */
final class FieldVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private AuthorizationCheckerInterface $security;
    private FieldRepositoryInterface      $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $field));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new FieldVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $state, [FieldVoter::CREATE_FIELD]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::UPDATE_FIELD]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::REMOVE_FIELD]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::DELETE_FIELD]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::GET_FIELD_PERMISSIONS]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::SET_FIELD_PERMISSIONS]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        /** @var \App\Repository\Contracts\StateRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [/* skipping */ , $stateB, $stateC] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        [/* skipping */ , $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));
    }

    /**
     * @covers ::isRemoveGranted
     * @covers ::voteOnAttribute
     */
    public function testRemove(): void
    {
        [/* skipping */ , $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        self::assertTrue($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldC));
        self::assertFalse($this->security->isGranted(FieldVoter::REMOVE_FIELD, $fieldD));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        [/* skipping */ , $fieldB, $fieldC, $fieldD] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        self::assertTrue($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldD));
    }

    /**
     * @covers ::isGetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testGetPermissions(): void
    {
        [/* skipping */ , $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::GET_FIELD_PERMISSIONS, $fieldB));
        self::assertTrue($this->security->isGranted(FieldVoter::GET_FIELD_PERMISSIONS, $fieldC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::GET_FIELD_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::GET_FIELD_PERMISSIONS, $fieldC));
    }

    /**
     * @covers ::isSetPermissionsGranted
     * @covers ::voteOnAttribute
     */
    public function testSetPermissions(): void
    {
        [/* skipping */ , $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $fieldB));
        self::assertTrue($this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $fieldC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $fieldC));
    }
}
