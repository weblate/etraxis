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

namespace App\Message\ListItems;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\ListItems\DeleteListItemCommand
 */
final class DeleteListItemCommandTest extends TestCase
{
    /**
     * @covers ::getItem
     */
    public function testConstructor(): void
    {
        $command = new DeleteListItemCommand(1);

        self::assertSame(1, $command->getItem());
    }
}
