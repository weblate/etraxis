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

namespace App\Message\States;

use App\Entity\Enums\SystemRoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\States\SetRolesTransitionCommand
 */
final class SetRolesTransitionCommandTest extends TestCase
{
    /**
     * @covers ::getFromState
     * @covers ::getRoles
     * @covers ::getToState
     */
    public function testConstructor(): void
    {
        $roles = [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ];

        $command = new SetRolesTransitionCommand(1, 2, $roles);

        self::assertSame(1, $command->getFromState());
        self::assertSame(2, $command->getToState());
        self::assertSame($roles, $command->getRoles());
    }
}
