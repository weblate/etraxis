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
 * @coversDefaultClass \App\Validator\DurationRange
 */
final class DurationRangeTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testSuccess(): void
    {
        $constraint = new DurationRange([
            'min'               => '1:00',
            'max'               => '10:00',
            'notInRangeMessage' => 'Must be in this range.',
            'minMessage'        => 'Must not be less than this.',
            'maxMessage'        => 'Must not be greater than this.',
            'invalidMessage'    => 'Must not be like this.',
        ]);

        self::assertSame('1:00', $constraint->min);
        self::assertSame('10:00', $constraint->max);
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
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "App\\Validator\\DurationRange".');

        new DurationRange();
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidMinOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "App\\Validator\\DurationRange" is invalid.');

        new DurationRange([
            'min' => '0:60',
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidMaxOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "App\\Validator\\DurationRange" is invalid.');

        new DurationRange([
            'max' => '0:60',
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testMinOptionOnly(): void
    {
        $constraint = new DurationRange([
            'min' => '1:00',
        ]);

        self::assertSame('1:00', $constraint->min);
        self::assertNull($constraint->max);
    }

    /**
     * @covers ::__construct
     */
    public function testMaxOptionOnly(): void
    {
        $constraint = new DurationRange([
            'max' => '10:00',
        ]);

        self::assertNull($constraint->min);
        self::assertSame('10:00', $constraint->max);
    }
}
