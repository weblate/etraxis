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

namespace App\Message;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Collection
 */
final class CollectionTest extends TestCase
{
    /**
     * @covers ::getItems
     * @covers ::getTotal
     */
    public function testConstructor(): void
    {
        $expected = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
        ];

        $collection = new Collection(7, $expected);

        self::assertSame(7, $collection->getTotal());
        self::assertSame($expected, $collection->getItems());
    }
}
