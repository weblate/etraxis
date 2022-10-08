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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Fields\SetGroupsPermissionCommand
 */
final class SetGroupsPermissionCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getGroups
     * @covers ::getPermission
     */
    public function testConstructor(): void
    {
        $groups = [1, 2, 3];

        $command = new SetGroupsPermissionCommand(1, FieldPermissionEnum::ReadAndWrite, $groups);

        self::assertSame(1, $command->getField());
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $command->getPermission());
        self::assertSame($groups, $command->getGroups());
    }
}
