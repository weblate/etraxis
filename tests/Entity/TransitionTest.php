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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Transition
 */
final class TransitionTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);

        self::assertSame($event, $transition->getEvent());
        self::assertSame($state, $transition->getState());
        self::assertEmpty($transition->getValues());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionEvent(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid event: IssueEdited');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        new Transition($event, $state);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionState(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state: foo');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $state2 = new State(new Template($template->getProject()), StateTypeEnum::Initial);
        $state2->setName('foo');

        new Transition($event, $state2);
    }

    /**
     * @covers ::__toString
     */
    public function testToString(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);

        $this->setProperty($transition, 'id', 1);
        self::assertSame('1', (string) $transition);
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);

        $this->setProperty($transition, 'id', 1);
        self::assertSame(1, $transition->getId());
    }

    /**
     * @covers ::getCreatedAt
     * @covers ::getEvent
     * @covers ::getUser
     */
    public function testEvent(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);
        self::assertSame($event, $transition->getEvent());
        self::assertSame($user, $transition->getUser());
        self::assertLessThanOrEqual(2, time() - $transition->getCreatedAt());
    }

    /**
     * @covers ::getState
     */
    public function testState(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);
        self::assertSame($state, $transition->getState());
    }

    /**
     * @covers ::getValues
     */
    public function testValues(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::StateChanged);

        $transition = new Transition($event, $state);
        self::assertEmpty($transition->getValues());

        /** @var \Doctrine\Common\Collections\Collection $values */
        $values = $this->getProperty($transition, 'values');
        $values->add('Value A');
        $values->add('Value B');

        self::assertSame(['Value A', 'Value B'], $transition->getValues()->getValues());
    }
}
