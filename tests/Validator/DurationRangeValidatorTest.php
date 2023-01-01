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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Validator\DurationRangeValidator
 */
final class DurationRangeValidatorTest extends WebTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get('validator');
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testSuccess(): void
    {
        $constraint = new DurationRange([
            'min' => '1:00',
            'max' => '10:00',
        ]);

        $errors = $this->validator->validate('0:59', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between 1:00 and 10:00.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10:01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between 1:00 and 10:00.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('1:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testMinOptionOnly(): void
    {
        $constraint = new DurationRange([
            'min' => '1:00',
        ]);

        $errors = $this->validator->validate('0:59', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 1:00 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('1:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::str2int
     * @covers ::validate
     */
    public function testMaxOptionOnly(): void
    {
        $constraint = new DurationRange([
            'max' => '10:00',
        ]);

        $errors = $this->validator->validate('10:01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 10:00 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10:00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0:60', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }
}
