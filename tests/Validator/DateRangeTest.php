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

namespace App\Validator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @internal
 *
 * @coversDefaultClass \App\Validator\DateRange
 */
final class DateRangeTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testSuccess(): void
    {
        $constraint = new DateRange([
            'min'               => '2015-11-22',
            'max'               => '2016-02-15',
            'notInRangeMessage' => 'Must be in this range.',
            'minMessage'        => 'Must not be less than this.',
            'maxMessage'        => 'Must not be greater than this.',
            'invalidMessage'    => 'Must not be like this.',
        ]);

        self::assertSame('2015-11-22', $constraint->min);
        self::assertSame('2016-02-15', $constraint->max);
        self::assertSame('Must be in this range.', $constraint->notInRangeMessage);
        self::assertSame('Must not be less than this.', $constraint->minMessage);
        self::assertSame('Must not be greater than this.', $constraint->maxMessage);
        self::assertSame('Must not be like this.', $constraint->invalidMessage);
    }

    /**
     * @covers ::__construct
     */
    public function testMissingOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "App\\Validator\\DateRange".');

        new DateRange();
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidMinOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "App\\Validator\\DateRange" is invalid.');

        new DateRange([
            'min' => '2015-22-11',
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidMaxOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "App\\Validator\\DateRange" is invalid.');

        new DateRange([
            'max' => '2015-22-11',
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testMinOptionOnly(): void
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
        ]);

        self::assertSame('2015-11-22', $constraint->min);
        self::assertNull($constraint->max);
    }

    /**
     * @covers ::__construct
     */
    public function testMaxOptionOnly(): void
    {
        $constraint = new DateRange([
            'max' => '2016-02-15',
        ]);

        self::assertNull($constraint->min);
        self::assertSame('2016-02-15', $constraint->max);
    }
}
