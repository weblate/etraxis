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
 * @coversDefaultClass \App\Validator\DecimalRangeValidator
 */
final class DecimalRangeValidatorTest extends WebTestCase
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
        $constraint = new DecimalRange([
            'min' => '-10',
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between -10 and +10.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between -10 and +10.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between -10 and +10.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be between -10 and +10.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
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
        $constraint = new DecimalRange([
            'min' => '-10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
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
        $constraint = new DecimalRange([
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }
}
