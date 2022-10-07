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

namespace App\Message\Templates;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Templates\SetRolesPermissionCommand
 */
final class SetRolesPermissionCommandTest extends TestCase
{
    /**
     * @covers ::getPermission
     * @covers ::getRoles
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $roles = [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ];

        $command = new SetRolesPermissionCommand(1, TemplatePermissionEnum::AddComments, $roles);

        self::assertSame(1, $command->getTemplate());
        self::assertSame(TemplatePermissionEnum::AddComments, $command->getPermission());
        self::assertSame($roles, $command->getRoles());
    }
}
