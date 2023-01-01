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

namespace App\Message\Templates;

use App\Entity\Enums\TemplatePermissionEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Templates\SetGroupsPermissionCommand
 */
final class SetGroupsPermissionCommandTest extends TestCase
{
    /**
     * @covers ::getGroups
     * @covers ::getPermission
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $groups = [1, 2, 3];

        $command = new SetGroupsPermissionCommand(1, TemplatePermissionEnum::AddComments, $groups);

        self::assertSame(1, $command->getTemplate());
        self::assertSame(TemplatePermissionEnum::AddComments, $command->getPermission());
        self::assertSame($groups, $command->getGroups());
    }
}
