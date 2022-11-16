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

use App\Entity\Field;
use App\ReflectionTrait;
use App\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\DecimalStrategy
 */
final class DecimalStrategyTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private DecimalStrategy     $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Test coverage']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->strategy->setParameter(Field::DEFAULT, $value);
        self::assertSame($value, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($value, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $min);
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $max);
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, null);
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMinimumValue(): void
    {
        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->strategy->setParameter(Field::MINIMUM, $value);
        self::assertSame($value, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $min);
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $max);
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->field->getParameter(Field::MINIMUM));
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMaximumValue(): void
    {
        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $this->strategy->setParameter(Field::MAXIMUM, $value);
        self::assertSame($value, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $min);
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $max);
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->field->getParameter(Field::MAXIMUM));
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
     * @covers ::toDecimal
     */
    public function testToInteger(): void
    {
        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        self::assertNull($this->callMethod($this->strategy, 'toDecimal', [null]));
        self::assertSame($value, $this->callMethod($this->strategy, 'toDecimal', [$value]));
        self::assertSame(DecimalStrategy::MIN_VALUE, $this->callMethod($this->strategy, 'toDecimal', [$min]));
        self::assertSame(DecimalStrategy::MAX_VALUE, $this->callMethod($this->strategy, 'toDecimal', [$max]));
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
        $this->strategy->setParameter(Field::MINIMUM, '0');
        $this->strategy->setParameter(Field::MAXIMUM, '100');

        $errors = $this->validator->validate('0', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.0000000000', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100.0000000000', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-0.000000001', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('100.0000000001', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->strategy->getValueValidationConstraints($this->translator));
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
