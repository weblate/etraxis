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

namespace App\Entity;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\FieldValue
 */
final class FieldValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $fieldValue = new FieldValue($transition, $field, null);

        self::assertSame($transition, $fieldValue->getTransition());
        self::assertSame($field, $fieldValue->getField());
        self::assertNull($fieldValue->getValue());

        $fieldValue = new FieldValue($transition, $field, 1);

        self::assertSame($transition, $fieldValue->getTransition());
        self::assertSame($field, $fieldValue->getField());
        self::assertSame(1, $fieldValue->getValue());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field: foo');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $state2 = new State($template, StateTypeEnum::Final);
        $field  = new Field($state2, FieldTypeEnum::Number);

        $field->setName('foo');

        new FieldValue($transition, $field, null);
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $fieldValue = new FieldValue($transition, $field, null);

        $this->setProperty($fieldValue, 'id', 1);
        self::assertSame(1, $fieldValue->getId());
    }

    /**
     * @covers ::getTransition
     */
    public function testTransition(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $fieldValue = new FieldValue($transition, $field, null);
        self::assertSame($transition, $fieldValue->getTransition());
    }

    /**
     * @covers ::getField
     */
    public function testField(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $fieldValue = new FieldValue($transition, $field, null);
        self::assertSame($field, $fieldValue->getField());
    }

    /**
     * @covers ::getValue
     * @covers ::setValue
     */
    public function testValue(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::StateChanged);
        $transition = new Transition($event, $state);

        $fieldValue = new FieldValue($transition, $field, 1);
        self::assertSame(1, $fieldValue->getValue());

        $fieldValue->setValue(2);
        self::assertSame(2, $fieldValue->getValue());

        $fieldValue->setValue(null);
        self::assertNull($fieldValue->getValue());
    }
}
