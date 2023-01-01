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

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldGroupPermission
 */
final class FieldGroupPermissionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project = new Project();
        $group   = new Group($project);
        $state   = new State(new Template($project), StateTypeEnum::Intermediate);
        $field   = new Field($state, FieldTypeEnum::List);

        $permission = new FieldGroupPermission($field, $group, FieldPermissionEnum::ReadAndWrite);

        self::assertSame($field, $permission->getField());
        self::assertSame($group, $permission->getGroup());
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $permission->getPermission());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionGroup(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: foo');

        $project1 = new Project();
        $project2 = new Project();
        $group    = new Group($project1);
        $state    = new State(new Template($project2), StateTypeEnum::Intermediate);
        $field    = new Field($state, FieldTypeEnum::List);

        $group->setName('foo');

        $permission = new FieldGroupPermission($field, $group, FieldPermissionEnum::ReadAndWrite);

        self::assertSame($field, $permission->getField());
        self::assertSame($group, $permission->getGroup());
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $permission->getPermission());
    }

    /**
     * @covers ::getField
     */
    public function testField(): void
    {
        $project = new Project();
        $group   = new Group($project);
        $state   = new State(new Template($project), StateTypeEnum::Intermediate);
        $field   = new Field($state, FieldTypeEnum::List);

        $permission = new FieldGroupPermission($field, $group, FieldPermissionEnum::ReadAndWrite);
        self::assertSame($field, $permission->getField());
    }

    /**
     * @covers ::getGroup
     */
    public function testGroup(): void
    {
        $project = new Project();
        $group   = new Group($project);
        $state   = new State(new Template($project), StateTypeEnum::Intermediate);
        $field   = new Field($state, FieldTypeEnum::List);

        $permission = new FieldGroupPermission($field, $group, FieldPermissionEnum::ReadAndWrite);
        self::assertSame($group, $permission->getGroup());
    }

    /**
     * @covers ::getPermission
     * @covers ::setPermission
     */
    public function testPermission(): void
    {
        $project = new Project();
        $group   = new Group($project);
        $state   = new State(new Template($project), StateTypeEnum::Intermediate);
        $field   = new Field($state, FieldTypeEnum::List);

        $permission = new FieldGroupPermission($field, $group, FieldPermissionEnum::ReadAndWrite);
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $permission->getPermission());

        $permission->setPermission(FieldPermissionEnum::ReadOnly);
        self::assertSame(FieldPermissionEnum::ReadOnly, $permission->getPermission());
    }
}
