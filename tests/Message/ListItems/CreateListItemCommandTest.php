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

namespace App\Message\ListItems;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\ListItems\CreateListItemCommand
 */
final class CreateListItemCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getText
     * @covers ::getValue
     */
    public function testConstructor(): void
    {
        $command = new CreateListItemCommand(1, 5, 'Friday');

        self::assertSame(1, $command->getField());
        self::assertSame(5, $command->getValue());
        self::assertSame('Friday', $command->getText());
    }
}
