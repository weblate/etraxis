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

namespace App\Entity\FieldStrategy;

use App\Entity\Field;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\DurationStrategy
 */
final class DurationStrategyTest extends WebTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private DurationStrategy    $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Effort']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getDefault
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        $value = '14:26';
        $min   = '0:00';
        $max   = '999999:59';

        $this->strategy->setParameter(Field::DEFAULT, $value);
        self::assertSame($value, $this->strategy->getDefault());
        self::assertSame($value, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($value, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $min);
        self::assertSame($min, $this->strategy->getDefault());
        self::assertSame($min, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($min, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $max);
        self::assertSame($max, $this->strategy->getDefault());
        self::assertSame($max, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($max, $this->field->getParameter(Field::DEFAULT));

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
        $value = '14:26';
        $min   = '0:00';
        $max   = '999999:59';

        $this->strategy->setParameter(Field::MINIMUM, $value);
        self::assertSame($value, $this->strategy->getMinimum());
        self::assertSame($value, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $min);
        self::assertSame($min, $this->strategy->getMinimum());
        self::assertSame($min, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame($min, $this->field->getParameter(Field::MINIMUM));

        $this->strategy->setParameter(Field::MINIMUM, $max);
        self::assertSame($max, $this->strategy->getMinimum());
        self::assertSame($max, $this->strategy->getParameter(Field::MINIMUM));
        self::assertSame($max, $this->field->getParameter(Field::MINIMUM));
    }

    /**
     * @covers ::getMaximum
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMaximumValue(): void
    {
        $value = '14:26';
        $min   = '0:00';
        $max   = '999999:59';

        $this->strategy->setParameter(Field::MAXIMUM, $value);
        self::assertSame($value, $this->strategy->getMaximum());
        self::assertSame($value, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame($value, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $min);
        self::assertSame($min, $this->strategy->getMaximum());
        self::assertSame($min, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame($min, $this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, $max);
        self::assertSame($max, $this->strategy->getMaximum());
        self::assertSame($max, $this->strategy->getParameter(Field::MAXIMUM));
        self::assertSame($max, $this->field->getParameter(Field::MAXIMUM));
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
        $this->strategy->setParameter(Field::MINIMUM, '0:00');
        $this->strategy->setParameter(Field::MAXIMUM, '24:00');

        $errors = $this->validator->validate('0:00', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:00', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:01', $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0:00 to 24:00.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0:60', $this->strategy->getValueValidationConstraints($this->translator));
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

    /**
     * @covers ::int2hhmm
     */
    public function testIntToHHMM(): void
    {
        self::assertNull(DurationStrategy::int2hhmm(null));
        self::assertSame('0:00', DurationStrategy::int2hhmm(DurationStrategy::MIN_VALUE - 1));
        self::assertSame('999999:59', DurationStrategy::int2hhmm(DurationStrategy::MAX_VALUE + 1));
        self::assertSame('0:00', DurationStrategy::int2hhmm(0));
        self::assertSame('14:26', DurationStrategy::int2hhmm(866));
    }

    /**
     * @covers ::hhmm2int
     */
    public function testHHMMToInt(): void
    {
        self::assertNull(DurationStrategy::hhmm2int(null));
        self::assertNull(DurationStrategy::hhmm2int('0:99'));
        self::assertSame(866, DurationStrategy::hhmm2int('14:26'));
    }
}
