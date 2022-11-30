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
use App\TransactionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\TextStrategy
 */
final class TextStrategyTest extends TransactionalTestCase
{
    private TranslatorInterface $translator;
    private ValidatorInterface  $validator;
    private Field               $field;
    private TextStrategy        $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
        $this->validator  = self::getContainer()->get('validator');

        /** @var \App\Repository\Contracts\FieldRepositoryInterface $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(Field::class);

        [/* skipping */ , $this->field] = $repository->findBy(['name' => 'Description']);

        $this->strategy = $this->field->getStrategy();
    }

    /**
     * @covers ::getDefault
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testDefaultValue(): void
    {
        $value = 'eTraxis';

        $this->strategy->setParameter(Field::DEFAULT, $value);
        self::assertSame($value, $this->strategy->getDefault());
        self::assertSame($value, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($value, $this->field->getParameter(Field::DEFAULT));

        $huge = str_pad('', TextStrategy::MAX_LENGTH + 1);
        $trim = str_pad('', TextStrategy::MAX_LENGTH);

        $this->strategy->setParameter(Field::DEFAULT, $huge);
        self::assertSame($trim, $this->strategy->getDefault());
        self::assertSame($trim, $this->strategy->getParameter(Field::DEFAULT));
        self::assertSame($trim, $this->field->getParameter(Field::DEFAULT));

        $this->strategy->setParameter(Field::DEFAULT, null);
        self::assertNull($this->strategy->getDefault());
        self::assertNull($this->strategy->getParameter(Field::DEFAULT));
        self::assertNull($this->field->getParameter(Field::DEFAULT));
    }

    /**
     * @covers ::getLength
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testMaximumLength(): void
    {
        $value = random_int(TextStrategy::MIN_LENGTH, TextStrategy::MAX_LENGTH);
        $min   = TextStrategy::MIN_LENGTH - 1;
        $max   = TextStrategy::MAX_LENGTH + 1;

        $this->strategy->setParameter(Field::LENGTH, $value);
        self::assertSame($value, $this->strategy->getLength());
        self::assertSame($value, $this->strategy->getParameter(Field::LENGTH));
        self::assertSame($value, $this->field->getParameter(Field::LENGTH));

        $this->strategy->setParameter(Field::LENGTH, $min);
        self::assertSame(TextStrategy::MIN_LENGTH, $this->strategy->getLength());
        self::assertSame(TextStrategy::MIN_LENGTH, $this->strategy->getParameter(Field::LENGTH));
        self::assertSame(TextStrategy::MIN_LENGTH, $this->field->getParameter(Field::LENGTH));

        $this->strategy->setParameter(Field::LENGTH, $max);
        self::assertSame(TextStrategy::MAX_LENGTH, $this->strategy->getLength());
        self::assertSame(TextStrategy::MAX_LENGTH, $this->strategy->getParameter(Field::LENGTH));
        self::assertSame(TextStrategy::MAX_LENGTH, $this->field->getParameter(Field::LENGTH));
    }

    /**
     * @covers ::getParameter
     * @covers ::getPcreCheck
     * @covers ::setParameter
     */
    public function testPcreCheck(): void
    {
        $value = '(\d{3})-(\d{3})-(\d{4})';

        $this->strategy->setParameter(Field::PCRE_CHECK, $value);
        self::assertSame($value, $this->strategy->getPcreCheck());
        self::assertSame($value, $this->strategy->getParameter(Field::PCRE_CHECK));
        self::assertSame($value, $this->field->getParameter(Field::PCRE_CHECK));

        $huge = str_pad('', Field::MAX_PCRE + 1);
        $trim = str_pad('', Field::MAX_PCRE);

        $this->strategy->setParameter(Field::PCRE_CHECK, $huge);
        self::assertSame($trim, $this->strategy->getPcreCheck());
        self::assertSame($trim, $this->strategy->getParameter(Field::PCRE_CHECK));
        self::assertSame($trim, $this->field->getParameter(Field::PCRE_CHECK));

        $this->strategy->setParameter(Field::PCRE_CHECK, null);
        self::assertNull($this->strategy->getPcreCheck());
        self::assertNull($this->strategy->getParameter(Field::PCRE_CHECK));
        self::assertNull($this->field->getParameter(Field::PCRE_CHECK));
    }

    /**
     * @covers ::getParameter
     * @covers ::getPcreSearch
     * @covers ::setParameter
     */
    public function testPcreSearch(): void
    {
        $value = '(\d{3})-(\d{3})-(\d{4})';

        $this->strategy->setParameter(Field::PCRE_SEARCH, $value);
        self::assertSame($value, $this->strategy->getPcreSearch());
        self::assertSame($value, $this->strategy->getParameter(Field::PCRE_SEARCH));
        self::assertSame($value, $this->field->getParameter(Field::PCRE_SEARCH));

        $huge = str_pad('', Field::MAX_PCRE + 1);
        $trim = str_pad('', Field::MAX_PCRE);

        $this->strategy->setParameter(Field::PCRE_SEARCH, $huge);
        self::assertSame($trim, $this->strategy->getPcreSearch());
        self::assertSame($trim, $this->strategy->getParameter(Field::PCRE_SEARCH));
        self::assertSame($trim, $this->field->getParameter(Field::PCRE_SEARCH));

        $this->strategy->setParameter(Field::PCRE_SEARCH, null);
        self::assertNull($this->strategy->getPcreSearch());
        self::assertNull($this->strategy->getParameter(Field::PCRE_SEARCH));
        self::assertNull($this->field->getParameter(Field::PCRE_SEARCH));
    }

    /**
     * @covers ::getParameter
     * @covers ::getPcreReplace
     * @covers ::setParameter
     */
    public function testPcreReplace(): void
    {
        $value = '($1) $2-$3';

        $this->strategy->setParameter(Field::PCRE_REPLACE, $value);
        self::assertSame($value, $this->strategy->getPcreReplace());
        self::assertSame($value, $this->strategy->getParameter(Field::PCRE_REPLACE));
        self::assertSame($value, $this->field->getParameter(Field::PCRE_REPLACE));

        $huge = str_pad('', Field::MAX_PCRE + 1);
        $trim = str_pad('', Field::MAX_PCRE);

        $this->strategy->setParameter(Field::PCRE_REPLACE, $huge);
        self::assertSame($trim, $this->strategy->getPcreReplace());
        self::assertSame($trim, $this->strategy->getParameter(Field::PCRE_REPLACE));
        self::assertSame($trim, $this->field->getParameter(Field::PCRE_REPLACE));

        $this->strategy->setParameter(Field::PCRE_REPLACE, null);
        self::assertNull($this->strategy->getPcreReplace());
        self::assertNull($this->strategy->getParameter(Field::PCRE_REPLACE));
        self::assertNull($this->field->getParameter(Field::PCRE_REPLACE));
    }

    /**
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testUnsupportedParameter(): void
    {
        self::assertNull($this->strategy->getParameter(Field::MAXIMUM));
        self::assertNull($this->field->getParameter(Field::MAXIMUM));

        $this->strategy->setParameter(Field::MAXIMUM, 123);
        self::assertNull($this->strategy->getParameter(Field::MAXIMUM));
        self::assertNull($this->field->getParameter(Field::MAXIMUM));
    }

    /**
     * @covers ::getParametersValidationConstraints
     */
    public function testParametersValidationConstraints(): void
    {
        $expected = ['default'];

        $constraints = $this->strategy->getParametersValidationConstraints($this->translator);
        self::assertSame($expected, array_keys($constraints));
    }

    /**
     * @covers ::getValueValidationConstraints
     */
    public function testValueValidationConstraints(): void
    {
        $this->strategy->setParameter(Field::LENGTH, 2000);
        $this->strategy->setParameter(Field::PCRE_CHECK, '(\*+)');

        $errors = $this->validator->validate(str_pad('', 2000, '*'), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(str_pad('', 2001, '*'), $this->strategy->getValueValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is too long. It should have 2000 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(str_pad('', 2000, '-'), $this->strategy->getValueValidationConstraints($this->translator));
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
