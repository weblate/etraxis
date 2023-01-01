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
 * @coversDefaultClass \App\Entity\FieldStrategy\CheckboxStrategy
 */
final class CheckboxStrategyTest extends WebTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private CheckboxStrategy    $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'New feature']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getDefault
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        self::assertTrue($this->strategy->getDefault());
        self::assertTrue($this->strategy->getParameter(Field::DEFAULT));
        self::assertTrue($this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, false);
        self::assertFalse($this->strategy->getDefault());
        self::assertFalse($this->strategy->getParameter(Field::DEFAULT));
        self::assertFalse($this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, true);
        self::assertTrue($this->strategy->getDefault());
        self::assertTrue($this->strategy->getParameter(Field::DEFAULT));
        self::assertTrue($this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, null);
        self::assertFalse($this->strategy->getDefault());
        self::assertFalse($this->strategy->getParameter(Field::DEFAULT));
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
        self::assertCount(0, $this->validator->validate(false, $this->strategy->getValueValidationConstraints($this->translator)));
        self::assertCount(0, $this->validator->validate(true, $this->strategy->getValueValidationConstraints($this->translator)));

        self::assertNotCount(0, $this->validator->validate(123, $this->strategy->getValueValidationConstraints($this->translator)));
    }
}
