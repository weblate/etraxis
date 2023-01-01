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

namespace App\Entity;

use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Group
 */
final class GroupTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project = new Project();

        $group = new Group($project);
        self::assertSame($project, $group->getProject());
        self::assertEmpty($group->getMembers());

        $group = new Group();
        self::assertNull($group->getProject());
        self::assertEmpty($group->getMembers());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $group = new Group();

        $this->setProperty($group, 'id', 1);
        self::assertSame(1, $group->getId());
    }

    /**
     * @covers ::getProject
     */
    public function testProject(): void
    {
        $project = new Project();

        $group = new Group($project);
        self::assertSame($project, $group->getProject());

        $group = new Group();
        self::assertNull($group->getProject());
    }

    /**
     * @covers ::isGlobal
     */
    public function testIsGlobal(): void
    {
        $project = new Project();

        $group = new Group($project);
        self::assertFalse($group->isGlobal());

        $group = new Group();
        self::assertTrue($group->isGlobal());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName(): void
    {
        $group = new Group();

        $group->setName('Members');
        self::assertSame('Members', $group->getName());
    }

    /**
     * @covers ::getDescription
     * @covers ::setDescription
     */
    public function testDescription(): void
    {
        $group = new Group();
        self::assertNull($group->getDescription());

        $group->setDescription('Lorem Ipsum');
        self::assertSame('Lorem Ipsum', $group->getDescription());
    }

    /**
     * @covers ::addMember
     * @covers ::getMembers
     * @covers ::removeMember
     */
    public function testMembers(): void
    {
        $group = new Group();
        self::assertEmpty($group->getMembers());

        $user1 = new User();
        $user2 = new User();

        $this->setProperty($user1, 'id', 1);
        $this->setProperty($user2, 'id', 2);

        $group->addMember($user1);
        $group->addMember($user2);
        self::assertSame([$user1, $user2], $group->getMembers()->getValues());

        $group->removeMember($user1);
        self::assertSame([$user2], $group->getMembers()->getValues());
    }
}
