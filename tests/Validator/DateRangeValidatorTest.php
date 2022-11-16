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

namespace App\Validator;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Validator\DateRangeValidator
 */
final class DateRangeValidatorTest extends WebTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get('validator');
    }

    /**
     * @covers ::validate
     */
    public function testSuccess(): void
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between 2015-11-22 and 2016-02-15.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between 2015-11-22 and 2016-02-15.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMinOptionOnly(): void
    {
        $constraint = new DateRange([
            'min' => '2015-11-22',
        ]);

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2015-11-22 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validate
     */
    public function testMaxOptionOnly(): void
    {
        $constraint = new DateRange([
            'max' => '2016-02-15',
        ]);

        $errors = $this->validator->validate('2016-02-16', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be 2016-02-15 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-21', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2016-02-15', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-22-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }
}
