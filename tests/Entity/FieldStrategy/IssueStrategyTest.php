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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\IssueStrategy
 */
final class IssueStrategyTest extends WebTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private IssueStrategy       $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Issue ID']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testUnsupportedParameter(): void
    {
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, true);
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));
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
        $errors = $this->validator->validate(1, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be greater than 0.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(-1, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(12.34, $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

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
