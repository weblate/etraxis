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

namespace App\Message\Users;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Users\RemoveGroupsCommand
 */
final class RemoveGroupsCommandTest extends TestCase
{
    /**
     * @covers ::getGroups
     * @covers ::getUser
     */
    public function testConstructor(): void
    {
        $groups = [1, 2, 3];

        $command = new RemoveGroupsCommand(1, $groups);

        self::assertSame(1, $command->getUser());
        self::assertSame($groups, $command->getGroups());
    }
}
