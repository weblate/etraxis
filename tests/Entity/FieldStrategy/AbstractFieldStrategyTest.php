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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\Field;
use App\Entity\Project;
use App\Entity\State;
use App\Entity\Template;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldStrategy\AbstractFieldStrategy
 */
final class AbstractFieldStrategyTest extends TestCase
{
    use ReflectionTrait;

    private Field                 $field;
    private AbstractFieldStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field = new Field(new State(new Template(new Project()), StateTypeEnum::Intermediate), FieldTypeEnum::List);

        $this->strategy = new class($this->field) extends AbstractFieldStrategy {};
    }

    /**
     * @covers ::getContext
     */
    public function testGetContext(): void
    {
        self::assertSame($this->field, $this->strategy->getContext());
    }

    /**
     * @covers ::getParameter
     */
    public function testGetParameter(): void
    {
        self::assertNull($this->strategy->getParameter('foo'));
    }

    /**
     * @covers ::setParameter
     */
    public function testSetParameter(): void
    {
        self::assertSame($this->strategy, $this->strategy->setParameter('foo', 'bar'));
    }

    /**
     * @covers ::getParametersValidationConstraints
     */
    public function testGetParametersValidationConstraints(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);

        self::assertSame([], $this->strategy->getParametersValidationConstraints($translator));
    }

    /**
     * @covers ::getValueValidationConstraints
     */
    public function testGetValueValidationConstraints(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $this->field->setRequired(true);
        self::assertCount(1, $this->strategy->getValueValidationConstraints($translator));

        $this->field->setRequired(false);
        self::assertCount(0, $this->strategy->getValueValidationConstraints($translator));
    }

    /**
     * @covers ::toBoolean
     */
    public function testToBoolean(): void
    {
        self::assertNull($this->callMethod($this->strategy, 'toBoolean', [null]));
        self::assertFalse($this->callMethod($this->strategy, 'toBoolean', [false]));
        self::assertTrue($this->callMethod($this->strategy, 'toBoolean', [true]));
        self::assertTrue($this->callMethod($this->strategy, 'toBoolean', [100]));
    }

    /**
     * @covers ::toInteger
     */
    public function testToInteger(): void
    {
        self::assertNull($this->callMethod($this->strategy, 'toInteger', [null]));
        self::assertSame(1, $this->callMethod($this->strategy, 'toInteger', [0, 1, 100]));
        self::assertSame(100, $this->callMethod($this->strategy, 'toInteger', [101, 1, 100]));
        self::assertSame(1000, $this->callMethod($this->strategy, 'toInteger', [1000]));
        self::assertSame(1000, $this->callMethod($this->strategy, 'toInteger', ['1000']));
    }

    /**
     * @covers ::toString
     */
    public function testToString(): void
    {
        self::assertNull($this->callMethod($this->strategy, 'toString', [null]));
        self::assertSame('eTraxis', $this->callMethod($this->strategy, 'toString', ['eTraxis']));
        self::assertSame('eTr', $this->callMethod($this->strategy, 'toString', ['eTraxis', 3]));
    }
}
