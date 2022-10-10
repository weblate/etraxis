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
 * @coversDefaultClass \App\Message\ListItems\UpdateListItemCommand
 */
final class UpdateListItemCommandTest extends TestCase
{
    /**
     * @covers ::getItem
     * @covers ::getText
     * @covers ::getValue
     */
    public function testConstructor(): void
    {
        $command = new UpdateListItemCommand(1, 5, 'Friday');

        self::assertSame(1, $command->getItem());
        self::assertSame(5, $command->getValue());
        self::assertSame('Friday', $command->getText());
    }
}
