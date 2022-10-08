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

namespace App\Message\Fields;

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\SystemRoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Fields\SetRolesPermissionCommand
 */
final class SetRolesPermissionCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getPermission
     * @covers ::getRoles
     */
    public function testConstructor(): void
    {
        $roles = [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ];

        $command = new SetRolesPermissionCommand(1, FieldPermissionEnum::ReadAndWrite, $roles);

        self::assertSame(1, $command->getField());
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $command->getPermission());
        self::assertSame($roles, $command->getRoles());
    }
}
