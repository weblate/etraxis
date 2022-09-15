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
 * @coversDefaultClass \App\Entity\Change
 */
final class ChangeTest extends TestCase
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

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);

        self::assertSame($event, $change->getEvent());
        self::assertSame($field, $change->getField());
        self::assertSame(1, $change->getOldValue());
        self::assertNull($change->getNewValue());

        $change = new Change($event, null, null, 1);

        self::assertSame($event, $change->getEvent());
        self::assertNull($change->getField());
        self::assertNull($change->getOldValue());
        self::assertSame(1, $change->getNewValue());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionEvent(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid event: IssueCreated');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueCreated);

        new Change($event, $field, 1, null);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionField(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field: foo');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $template2 = new Template($template->getProject());
        $state2    = new State($template2, StateTypeEnum::Final);
        $field     = new Field($state2, FieldTypeEnum::Number);

        $field->setName('foo');

        new Change($event, $field, 1, null);
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

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);

        $this->setProperty($change, 'id', 1);
        self::assertSame(1, $change->getId());
    }

    /**
     * @covers ::getEvent
     */
    public function testEvent(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);
        self::assertSame($event, $change->getEvent());
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

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);
        self::assertSame($field, $change->getField());

        $change = new Change($event, null, 1, null);
        self::assertNull($change->getField());
    }

    /**
     * @covers ::getOldValue
     */
    public function testOldValue(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);
        self::assertSame(1, $change->getOldValue());

        $change = new Change($event, $field, null, 1);
        self::assertNull($change->getOldValue());
    }

    /**
     * @covers ::getNewValue
     */
    public function testNewValue(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $field    = new Field($state, FieldTypeEnum::Number);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $change = new Change($event, $field, 1, null);
        self::assertNull($change->getNewValue());

        $change = new Change($event, $field, null, 1);
        self::assertSame(1, $change->getNewValue());
    }
}
