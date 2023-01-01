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

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\States\SetGroupsTransitionCommand
 */
final class SetGroupsTransitionCommandTest extends TestCase
{
    /**
     * @covers ::getFromState
     * @covers ::getGroups
     * @covers ::getToState
     */
    public function testConstructor(): void
    {
        $groups = [1, 2, 3];

        $command = new SetGroupsTransitionCommand(1, 2, $groups);

        self::assertSame(1, $command->getFromState());
        self::assertSame(2, $command->getToState());
        self::assertSame($groups, $command->getGroups());
    }
}
