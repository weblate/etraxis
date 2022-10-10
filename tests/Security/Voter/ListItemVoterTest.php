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

use App\Entity\Field;
use App\Entity\ListItem;
use App\LoginTrait;
use App\ReflectionTrait;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\ListItemVoter
 */
final class ListItemVoterTest extends TransactionalTestCase
{
    use LoginTrait;
    use ReflectionTrait;

    private ?AuthorizationCheckerInterface               $security;
    private ObjectRepository|ListItemRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security   = self::getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute(): void
    {
        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $item));
    }

    /**
     * @covers ::voteOnAttribute
     */
    public function testAnonymous(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ListItemVoter($manager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [/* skipping */ , $item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $field, [ListItemVoter::CREATE_LISTITEM]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::UPDATE_LISTITEM]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::DELETE_LISTITEM]));
    }

    /**
     * @covers ::isCreateGranted
     * @covers ::voteOnAttribute
     */
    public function testCreate(): void
    {
        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(Field::class);

        [/* skipping */ , $fieldB, $fieldC] = $repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [/* skipping */ , $fieldW] = $repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldB));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldW));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldB));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $fieldW));
    }

    /**
     * @covers ::isUpdateGranted
     * @covers ::voteOnAttribute
     */
    public function testUpdate(): void
    {
        [/* skipping */ , $itemB, $itemC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::UPDATE_LISTITEM, $itemB));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_LISTITEM, $itemC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_LISTITEM, $itemB));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_LISTITEM, $itemC));
    }

    /**
     * @covers ::isDeleteGranted
     * @covers ::voteOnAttribute
     */
    public function testDelete(): void
    {
        [/* skipping */ , $highB, $highC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);
        [/* skipping */ , $lowB, $lowC]   = $this->repository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->loginUser('admin@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $highB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $highC));
        self::assertTrue($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $lowB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $lowC));

        $this->loginUser('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $highB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $highC));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $lowB));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $lowC));
    }
}
