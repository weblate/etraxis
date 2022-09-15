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

namespace App\Entity;

use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\DecimalValue
 */
final class DecimalValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $decimal = new DecimalValue('0');
        self::assertSame('0', $decimal->getValue());

        $decimal = new DecimalValue('1234567890.0987654321');
        self::assertSame('1234567890.0987654321', $decimal->getValue());

        $decimal = new DecimalValue('0100');
        self::assertSame('100', $decimal->getValue());

        $decimal = new DecimalValue('03.1415000000');
        self::assertSame('3.1415', $decimal->getValue());

        $decimal = new DecimalValue('00.1415000000');
        self::assertSame('0.1415', $decimal->getValue());

        $decimal = new DecimalValue('03.0000000000');
        self::assertSame('3', $decimal->getValue());

        $decimal = new DecimalValue('00.0000000000');
        self::assertSame('0', $decimal->getValue());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $decimal = new DecimalValue('1234567890.0987654321');

        $this->setProperty($decimal, 'id', 1);
        self::assertSame(1, $decimal->getId());
    }

    /**
     * @covers ::getValue
     */
    public function testValue(): void
    {
        $decimal = new DecimalValue('1234567890.0987654321');

        self::assertSame('1234567890.0987654321', $decimal->getValue());
    }
}
