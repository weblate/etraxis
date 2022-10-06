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

namespace App\Message\Users;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Users\EnableUsersCommand
 */
final class EnableUsersCommandTest extends TestCase
{
    /**
     * @covers ::getUsers
     */
    public function testConstructor(): void
    {
        $users = [1, 2, 3];

        $command = new EnableUsersCommand($users);

        self::assertSame($users, $command->getUsers());
    }
}
