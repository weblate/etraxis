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

namespace App\Message\Groups;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Groups\DeleteGroupCommand
 */
final class DeleteGroupCommandTest extends TestCase
{
    /**
     * @covers ::getGroup
     */
    public function testConstructor(): void
    {
        $command = new DeleteGroupCommand(1);

        self::assertSame(1, $command->getGroup());
    }
}
