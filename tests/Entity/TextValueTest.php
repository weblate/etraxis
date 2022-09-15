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
 * @coversDefaultClass \App\Entity\TextValue
 */
final class TextValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $expected = str_pad('', TextValue::MAX_VALUE, '_');
        $text     = new TextValue($expected);

        self::assertSame(md5($expected), $this->getProperty($text, 'hash'));
        self::assertSame($expected, $text->getValue());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $expected = 'Lorem ipsum';
        $text     = new TextValue($expected);

        $this->setProperty($text, 'id', 1);
        self::assertSame(1, $text->getId());
    }

    /**
     * @covers ::getValue
     */
    public function testValue(): void
    {
        $expected = 'Lorem ipsum';
        $text     = new TextValue($expected);

        self::assertSame($expected, $text->getValue());
    }
}
