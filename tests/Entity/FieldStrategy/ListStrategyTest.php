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
use App\Entity\ListItem;
use App\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\ListStrategy
 */
final class ListStrategyTest extends TransactionalTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private ListStrategy        $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Priority']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        $value = random_int(ListStrategy::MIN_VALUE, PHP_INT_MAX);
        $min   = ListStrategy::MIN_VALUE - 1;

        $this->strategy->setParameter(Field::DEFAULT, $value);
        self::assertSame($value, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($value, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, $min);
        self::assertSame(ListStrategy::MIN_VALUE, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame(ListStrategy::MIN_VALUE, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, null);
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));
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
        $expected = [];

        $constraints = $this->strategy->getParametersValidationConstraints($this->translator);
        self::assertSame($expected, array_keys($constraints));
    }

    /**
     * @covers ::getValueValidationConstraints
     */
    public function testValueValidationConstraints(): void
    {
        /** @var \App\Repository\Contracts\ListItemRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(ListItem::class);

        $errors = $this->validator->validate(1, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(3, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be greater than 0.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(4, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('The value you selected is not a valid choice.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(-1, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(12.34, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->field->setRequired(true);

        $errors = $this->validator->validate(null, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->field->setRequired(false);

        $errors = $this->validator->validate(null, $this->strategy->getValueValidationConstraints($this->translator, ['repository' => $repository]));
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::getValueValidationConstraints
     */
    public function testValueValidationConstraintsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Context must contain the ListItem repository.');

        $this->validator->validate(1, $this->strategy->getValueValidationConstraints($this->translator));
    }
}
