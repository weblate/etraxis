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
 * @coversDefaultClass \App\Entity\StringValue
 */
final class StringValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $expected = str_pad('', StringValue::MAX_VALUE, '_');
        $string   = new StringValue($expected);

        self::assertSame(md5($expected), $this->getProperty($string, 'hash'));
        self::assertSame($expected, $string->getValue());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $expected = 'Lorem ipsum';
        $string   = new StringValue($expected);

        $this->setProperty($string, 'id', 1);
        self::assertSame(1, $string->getId());
    }

    /**
     * @covers ::getValue
     */
    public function testValue(): void
    {
        $expected = 'Lorem ipsum';
        $string   = new StringValue($expected);

        self::assertSame($expected, $string->getValue());
    }
}
