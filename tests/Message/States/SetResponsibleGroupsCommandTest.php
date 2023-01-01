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
 * @coversDefaultClass \App\Message\States\SetResponsibleGroupsCommand
 */
final class SetResponsibleGroupsCommandTest extends TestCase
{
    /**
     * @covers ::getGroups
     * @covers ::getState
     */
    public function testConstructor(): void
    {
        $groups = [1, 2, 3];

        $command = new SetResponsibleGroupsCommand(1, $groups);

        self::assertSame(1, $command->getState());
        self::assertSame($groups, $command->getGroups());
    }
}
