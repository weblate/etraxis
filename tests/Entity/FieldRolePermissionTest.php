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
use App\Entity\Enums\SystemRoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldRolePermission
 */
final class FieldRolePermissionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        $field = new Field($state, FieldTypeEnum::List);

        $permission = new FieldRolePermission($field, SystemRoleEnum::Author, FieldPermissionEnum::ReadAndWrite);

        self::assertSame($field, $permission->getField());
        self::assertSame(SystemRoleEnum::Author, $permission->getRole());
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $permission->getPermission());
    }

    /**
     * @covers ::getField
     */
    public function testField(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        $field = new Field($state, FieldTypeEnum::List);

        $permission = new FieldRolePermission($field, SystemRoleEnum::Author, FieldPermissionEnum::ReadAndWrite);
        self::assertSame($field, $permission->getField());
    }

    /**
     * @covers ::getRole
     */
    public function testRole(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        $field = new Field($state, FieldTypeEnum::List);

        $permission = new FieldRolePermission($field, SystemRoleEnum::Author, FieldPermissionEnum::ReadAndWrite);
        self::assertSame(SystemRoleEnum::Author, $permission->getRole());
    }

    /**
     * @covers ::getPermission
     * @covers ::setPermission
     */
    public function testPermission(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        $field = new Field($state, FieldTypeEnum::List);

        $permission = new FieldRolePermission($field, SystemRoleEnum::Author, FieldPermissionEnum::ReadAndWrite);
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $permission->getPermission());

        $permission->setPermission(FieldPermissionEnum::ReadOnly);
        self::assertSame(FieldPermissionEnum::ReadOnly, $permission->getPermission());
    }
}
