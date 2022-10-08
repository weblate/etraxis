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

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Fields\SetFieldPositionCommand
 */
final class SetFieldPositionCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getPosition
     */
    public function testConstructor(): void
    {
        $command = new SetFieldPositionCommand(1, 2);

        self::assertSame(1, $command->getField());
        self::assertSame(2, $command->getPosition());
    }
}
