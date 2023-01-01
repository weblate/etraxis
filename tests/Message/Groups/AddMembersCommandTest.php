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

namespace App\Message\Groups;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Groups\AddMembersCommand
 */
final class AddMembersCommandTest extends TestCase
{
    /**
     * @covers ::getGroup
     * @covers ::getUsers
     */
    public function testConstructor(): void
    {
        $users = [1, 2, 3];

        $command = new AddMembersCommand(1, $users);

        self::assertSame(1, $command->getGroup());
        self::assertSame($users, $command->getUsers());
    }
}
