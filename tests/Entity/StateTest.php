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

namespace App\Entity;

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\State
 */
final class StateTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $template = new Template(new Project());

        $state = new State($template, StateTypeEnum::Initial);

        self::assertSame($template, $state->getTemplate());
        self::assertSame(StateTypeEnum::Initial, $state->getType());
        self::assertSame(StateResponsibleEnum::Remove, $state->getResponsible());
        self::assertEmpty($state->getFields());
        self::assertEmpty($state->getRoleTransitions());
        self::assertEmpty($state->getGroupTransitions());
        self::assertEmpty($state->getResponsibleGroups());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $this->setProperty($state, 'id', 1);
        self::assertSame(1, $state->getId());
    }

    /**
     * @covers ::getTemplate
     */
    public function testTemplate(): void
    {
        $template = new Template(new Project());

        $state = new State($template, StateTypeEnum::Intermediate);
        self::assertSame($template, $state->getTemplate());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Initial);

        $state->setName('New');
        self::assertSame('New', $state->getName());
    }

    /**
     * @covers ::getType
     */
    public function testType(): void
    {
        $template = new Template(new Project());

        $initial      = new State($template, StateTypeEnum::Initial);
        $intermediate = new State($template, StateTypeEnum::Intermediate);
        $final        = new State($template, StateTypeEnum::Final);

        self::assertSame(StateTypeEnum::Initial, $initial->getType());
        self::assertSame(StateTypeEnum::Intermediate, $intermediate->getType());
        self::assertSame(StateTypeEnum::Final, $final->getType());
    }

    /**
     * @covers ::isFinal
     */
    public function testIsFinal(): void
    {
        $template = new Template(new Project());

        $initial      = new State($template, StateTypeEnum::Initial);
        $intermediate = new State($template, StateTypeEnum::Intermediate);
        $final        = new State($template, StateTypeEnum::Final);

        self::assertFalse($initial->isFinal());
        self::assertFalse($intermediate->isFinal());
        self::assertTrue($final->isFinal());
    }

    /**
     * @covers ::getResponsible
     * @covers ::setResponsible
     */
    public function testResponsible(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        self::assertSame(StateResponsibleEnum::Remove, $state->getResponsible());

        $state->setResponsible(StateResponsibleEnum::Assign);
        self::assertSame(StateResponsibleEnum::Assign, $state->getResponsible());

        $state->setResponsible(StateResponsibleEnum::Keep);
        self::assertSame(StateResponsibleEnum::Keep, $state->getResponsible());

        $state->setResponsible(StateResponsibleEnum::Remove);
        self::assertSame(StateResponsibleEnum::Remove, $state->getResponsible());
    }

    /**
     * @covers ::getResponsible
     * @covers ::setResponsible
     */
    public function testResponsibleFinal(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Final);

        $state->setResponsible(StateResponsibleEnum::Assign);
        self::assertSame(StateResponsibleEnum::Remove, $state->getResponsible());
    }

    /**
     * @covers ::getFields
     */
    public function testFields(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        self::assertEmpty($state->getFields());

        $field1 = new Field($state, FieldTypeEnum::String);
        $field2 = new Field($state, FieldTypeEnum::String);
        $field3 = new Field($state, FieldTypeEnum::String);

        /** @var \Doctrine\Common\Collections\Collection $fields */
        $fields = $this->getProperty($state, 'fields');

        $fields->add($field1);
        $fields->add($field2);
        $fields->add($field3);

        self::assertSame([$field1, $field2, $field3], $state->getFields()->getValues());

        $field3->remove();

        self::assertSame([$field1, $field2], $state->getFields()->getValues());
    }

    /**
     * @covers ::getRoleTransitions
     */
    public function testRoleTransitions(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        self::assertEmpty($state->getRoleTransitions());

        /** @var \Doctrine\Common\Collections\Collection $transitions */
        $transitions = $this->getProperty($state, 'roleTransitions');
        $transitions->add('Transition A');
        $transitions->add('Transition B');

        self::assertSame(['Transition A', 'Transition B'], $state->getRoleTransitions()->getValues());
    }

    /**
     * @covers ::getGroupTransitions
     */
    public function testGroupTransitions(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        self::assertEmpty($state->getGroupTransitions());

        /** @var \Doctrine\Common\Collections\Collection $transitions */
        $transitions = $this->getProperty($state, 'groupTransitions');
        $transitions->add('Transition A');
        $transitions->add('Transition B');

        self::assertSame(['Transition A', 'Transition B'], $state->getGroupTransitions()->getValues());
    }

    /**
     * @covers ::getResponsibleGroups
     */
    public function testResponsibleGroups(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);
        self::assertEmpty($state->getResponsibleGroups());

        /** @var \Doctrine\Common\Collections\Collection $groups */
        $groups = $this->getProperty($state, 'responsibleGroups');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $state->getResponsibleGroups()->getValues());
    }
}
