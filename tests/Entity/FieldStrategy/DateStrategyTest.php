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

namespace App\Entity\FieldStrategy;

use App\Entity\Enums\SecondsEnum;
use App\Entity\Field;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\DateStrategy
 */
final class DateStrategyTest extends WebTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private DateStrategy        $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Due date']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getDefault
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        $value = random_int(DateStrategy::MIN_VALUE, DateStrategy::MAX_VALUE);
        $min   = DateStrategy::MIN_VALUE - 1;
        $max   = DateStrategy::MAX_VALUE + 1;

        $this->strategy->setParameter(Field::DEFAULT, $value);
        self::assertSame($value, $this->strategy->getDefault());
        self::assertSame($value, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($value, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $min);
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getDefault());
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame(DateStrategy::MIN_VALUE, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $max);
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getDefault());
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame(DateStrategy::MAX_VALUE, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, null);
        self::assertNull($this->strategy->getDefault());
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));
    }

    /**
     * @covers ::getMinimum
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMinimumValue(): void
    {
        $value = random_int(DateStrategy::MIN_VALUE, DateStrategy::MAX_VALUE);
        $min   = DateStrategy::MIN_VALUE - 1;
        $max   = DateStrategy::MAX_VALUE + 1;

        $this->strategy->setParameter(Field::MINIMUM, $value);
        self::assertSame($value, $this->strategy->getMinimum());
        self::assertSame($value, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $min);
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getMinimum());
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame(DateStrategy::MIN_VALUE, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $max);
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getMinimum());
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame(DateStrategy::MAX_VALUE, $this->field->getParameter(Field::MINIMUM));
    }

    /**
     * @covers ::getMaximum
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMaximumValue(): void
    {
        $value = random_int(DateStrategy::MIN_VALUE, DateStrategy::MAX_VALUE);
        $min   = DateStrategy::MIN_VALUE - 1;
        $max   = DateStrategy::MAX_VALUE + 1;

        $this->strategy->setParameter(Field::MAXIMUM, $value);
        self::assertSame($value, $this->strategy->getMaximum());
        self::assertSame($value, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $min);
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getMaximum());
        self::assertSame(DateStrategy::MIN_VALUE, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame(DateStrategy::MIN_VALUE, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $max);
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getMaximum());
        self::assertSame(DateStrategy::MAX_VALUE, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame(DateStrategy::MAX_VALUE, $this->field->getParameter(Field::MAXIMUM));
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testUnsupportedParameter(): void
    {
        self::assertNull($this->strategy->getParameter(Field::LENGTH));
        self::assertNull($this->field->getParameter(Field::LENGTH));

        $this->strategy->setParameter(Field::LENGTH, 123);
        self::assertNull($this->strategy->getParameter(Field::LENGTH));
        self::assertNull($this->field->getParameter(Field::LENGTH));
    }

    /**
     * @covers ::getParametersValidationConstraints
     */
    public function testParametersValidationConstraints(): void
    {
        $expected = ['maximum', 'default'];

        $constraints = $this->strategy->getParametersValidationConstraints($this->translator);
        self::assertSame($expected, array_keys($constraints));
    }

    /**
     * @covers ::getValueValidationConstraints
     */
    public function testValueValidationConstraints(): void
    {
        $this->field->setName('Custom field');
        $this->strategy->setParameter(Field::MINIMUM, 0);
        $this->strategy->setParameter(Field::MAXIMUM, 7);

        $now = time();

        $errors = $this->validator->validate(date('Y-m-d', $now), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now + SecondsEnum::OneDay->value * 7), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now - SecondsEnum::OneDay->value), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + SecondsEnum::OneDay->value * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate(date('Y-m-d', $now + SecondsEnum::OneDay->value * 8), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + SecondsEnum::OneDay->value * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-22', $this->strategy->getValueValidationConstraints($this->translator, [
            $this->field->getId() => strtotime('2015-11-22'),
        ]));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-11-29', $this->strategy->getValueValidationConstraints($this->translator, [
            $this->field->getId() => strtotime('2015-11-22'),
        ]));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('2015-11-21', $this->strategy->getValueValidationConstraints($this->translator, [
            $this->field->getId() => strtotime('2015-11-22'),
        ]));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 11/22/15 to 11/29/15.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-11-30', $this->strategy->getValueValidationConstraints($this->translator, [
            $this->field->getId() => strtotime('2015-11-22'),
        ]));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 11/22/15 to 11/29/15.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-22-11', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->field->setRequired(true);

        $errors = $this->validator->validate(null, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->field->setRequired(false);

        $errors = $this->validator->validate(null, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }
}
