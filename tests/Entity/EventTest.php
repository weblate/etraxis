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
use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Event
 */
final class EventTest extends TestCase
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
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertSame(EventTypeEnum::IssueEdited, $event->getType());
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());
        self::assertNull($event->getParameter());

        $this->setProperty($issue, 'createdAt', 123456789);
        $event = new Event($issue, $user, EventTypeEnum::IssueCreated, 'Artem Rodygin');

        self::assertSame($issue, $event->getIssue());
        self::assertSame($user, $event->getUser());
        self::assertSame(EventTypeEnum::IssueCreated, $event->getType());
        self::assertSame(123456789, $event->getCreatedAt());
        self::assertSame('Artem Rodygin', $event->getParameter());
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
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        $this->setProperty($event, 'id', 1);
        self::assertSame(1, $event->getId());
    }

    /**
     * @covers ::getIssue
     */
    public function testIssue(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
        self::assertSame($issue, $event->getIssue());
    }

    /**
     * @covers ::getUser
     */
    public function testUser(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
        self::assertSame($user, $event->getUser());
    }

    /**
     * @covers ::getType
     */
    public function testType(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
        self::assertSame(EventTypeEnum::IssueEdited, $event->getType());
    }

    /**
     * @covers ::getCreatedAt
     */
    public function testCreatedAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $this->setProperty($issue, 'createdAt', 123456789);

        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
        self::assertLessThanOrEqual(2, time() - $event->getCreatedAt());

        $event = new Event($issue, $user, EventTypeEnum::IssueCreated, 'Artem Rodygin');
        self::assertSame(123456789, $event->getCreatedAt());
    }

    /**
     * @covers ::getParameter
     */
    public function testParameter(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);

        $event = new Event($issue, $user, EventTypeEnum::IssueCreated, 'Artem Rodygin');
        self::assertSame('Artem Rodygin', $event->getParameter());

        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
        self::assertNull($event->getParameter());
    }
}
