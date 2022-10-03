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
 * @coversDefaultClass \App\Message\AbstractCollectionQuery
 */
final class AbstractCollectionQueryTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getFilters
     * @covers ::getLimit
     * @covers ::getOffset
     * @covers ::getOrder
     * @covers ::getSearch
     */
    public function testConstructor(): void
    {
        $query = new class(0, 10) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->getOffset());
        self::assertSame(10, $query->getLimit());
        self::assertNull($query->getSearch());
        self::assertSame([], $query->getFilters());
        self::assertSame([], $query->getOrder());
    }

    /**
     * @covers ::__construct
     * @covers ::getOffset
     */
    public function testOffset(): void
    {
        $query = new class(30, 10) extends AbstractCollectionQuery {};

        self::assertSame(30, $query->getOffset());
    }

    /**
     * @covers ::__construct
     * @covers ::getOffset
     */
    public function testOffsetNegative(): void
    {
        $query = new class(PHP_INT_MIN, 10) extends AbstractCollectionQuery {};

        self::assertSame(0, $query->getOffset());
    }

    /**
     * @covers ::__construct
     * @covers ::getOffset
     */
    public function testOffsetHuge(): void
    {
        $query = new class(PHP_INT_MAX, 10) extends AbstractCollectionQuery {};

        self::assertSame(PHP_INT_MAX, $query->getOffset());
    }

    /**
     * @covers ::__construct
     * @covers ::getLimit
     */
    public function testLimit(): void
    {
        $query = new class(0, 5) extends AbstractCollectionQuery {};

        self::assertSame(5, $query->getLimit());
    }

    /**
     * @covers ::__construct
     * @covers ::getLimit
     */
    public function testLimitZero(): void
    {
        $query = new class(0, 0) extends AbstractCollectionQuery {};

        self::assertSame(1, $query->getLimit());
    }

    /**
     * @covers ::__construct
     * @covers ::getLimit
     */
    public function testLimitNegative(): void
    {
        $query = new class(0, PHP_INT_MIN) extends AbstractCollectionQuery {};

        self::assertSame(1, $query->getLimit());
    }

    /**
     * @covers ::__construct
     * @covers ::getLimit
     */
    public function testLimitHuge(): void
    {
        $query = new class(0, PHP_INT_MAX) extends AbstractCollectionQuery {};

        self::assertSame(AbstractCollectionQuery::MAX_LIMIT, $query->getLimit());
    }

    /**
     * @covers ::getSearch
     */
    public function testSearch(): void
    {
        $query = new class(0, 10, 'mAn') extends AbstractCollectionQuery {};

        self::assertSame('mAn', $query->getSearch());
    }

    /**
     * @covers ::getFilters
     */
    public function testFilters(): void
    {
        $filters = [
            'email'       => 'eR',
            'description' => 'a*',
        ];

        $query = new class(0, 10, null, $filters) extends AbstractCollectionQuery {};

        self::assertSame($filters, $query->getFilters());
    }

    /**
     * @covers ::getOrder
     */
    public function testOrder(): void
    {
        $order = [
            'provider' => 'desc',
            'fullname' => 'asc',
        ];

        $query = new class(0, 10, null, [], $order) extends AbstractCollectionQuery {};

        self::assertSame($order, $query->getOrder());
    }
}
