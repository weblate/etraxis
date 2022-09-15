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
     * @covers ::getMembers
     */
    public function testMembers(): void
    {
        $group = new Group();
        self::assertEmpty($group->getMembers());

        /** @var \Doctrine\Common\Collections\Collection $members */
        $members = $this->getProperty($group, 'members');
        $members->add('User A');
        $members->add('User B');

        self::assertSame(['User A', 'User B'], $group->getMembers()->getValues());
    }
}
