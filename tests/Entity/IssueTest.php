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

use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Issue
 */
final class IssueTest extends TestCase
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

        self::assertSame($state, $issue->getState());
        self::assertSame($user, $issue->getAuthor());
        self::assertLessThanOrEqual(2, time() - $issue->getCreatedAt());
        self::assertSame($issue->getCreatedAt(), $issue->getChangedAt());
        self::assertEmpty($issue->getEvents());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Template has no initial state');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Intermediate);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        new Issue($template, $user);
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        $this->setProperty($issue, 'id', 1);
        self::assertSame(1, $issue->getId());
    }

    /**
     * @covers ::getFullId
     */
    public function testFullId(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        $template->setPrefix('bug');

        $this->setProperty($issue, 'id', 1);
        self::assertSame('bug-001', $issue->getFullId());

        $this->setProperty($issue, 'id', 1234);
        self::assertSame('bug-1234', $issue->getFullId());
    }

    /**
     * @covers ::getSubject
     * @covers ::setSubject
     */
    public function testSubject(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        $issue->setSubject('Fix that bug.');
        self::assertSame('Fix that bug.', $issue->getSubject());
    }

    /**
     * @covers ::getProject
     */
    public function testProject(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());
        self::assertSame($project, $issue->getProject());
    }

    /**
     * @covers ::getTemplate
     */
    public function testTemplate(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());
        self::assertSame($template, $issue->getTemplate());
    }

    /**
     * @covers ::getState
     * @covers ::setState
     */
    public function testState(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $state2   = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);
        $states->add($state2);

        $issue = new Issue($template, new User());
        self::assertSame($state, $issue->getState());

        $issue->setState($state2);
        self::assertSame($state2, $issue->getState());
    }

    /**
     * @covers ::setState
     */
    public function testStateException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state: foo');

        $project   = new Project();
        $template  = new Template($project);
        $template2 = new Template($project);
        $state     = new State($template, StateTypeEnum::Initial);
        $state2    = new State($template2, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template2, 'states');
        $states->add($state2);

        $state->setName('bar');
        $state2->setName('foo');

        $issue = new Issue($template, new User());
        self::assertSame($state, $issue->getState());

        $issue->setState($state2);
    }

    /**
     * @covers ::getAuthor
     */
    public function testAuthor(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        self::assertSame($user, $issue->getAuthor());
    }

    /**
     * @covers ::getResponsible
     * @covers ::setResponsible
     */
    public function testResponsible(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());
        self::assertNull($issue->getResponsible());

        $issue->setResponsible($user);
        self::assertSame($user, $issue->getResponsible());
        self::assertNotSame($issue->getAuthor(), $issue->getResponsible());
    }

    /**
     * @covers ::getOrigin
     * @covers ::isCloned
     * @covers ::setOrigin
     */
    public function testOrigin(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        self::assertNull($issue->getOrigin());
        self::assertFalse($issue->isCloned());

        $cloned = new Issue($template, new User());
        $cloned->setOrigin($issue);

        self::assertSame($issue, $cloned->getOrigin());
        self::assertTrue($cloned->isCloned());
    }

    /**
     * @covers ::setOrigin
     */
    public function testOriginException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid origin: bug-001');

        $project   = new Project();
        $template  = new Template($project);
        $template2 = new Template($project);
        $state     = new State($template, StateTypeEnum::Initial);
        $state2    = new State($template2, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template2, 'states');
        $states->add($state2);

        $issue = new Issue($template, new User());

        $template->setPrefix('bug');
        $this->setProperty($issue, 'id', 1);

        self::assertNull($issue->getOrigin());
        self::assertFalse($issue->isCloned());

        $cloned = new Issue($template2, new User());
        $cloned->setOrigin($issue);
    }

    /**
     * @covers ::getCreatedAt
     */
    public function testCreatedAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        self::assertLessThanOrEqual(2, time() - $issue->getCreatedAt());
    }

    /**
     * @covers ::getAge
     */
    public function testAge(): void
    {
        $template = new Template(new Project());
        $initial  = new State($template, StateTypeEnum::Initial);
        $final    = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($initial);
        $states->add($final);

        $issue = new Issue($template, new User());
        self::assertSame(0, $issue->getAge());

        $this->setProperty($issue, 'createdAt', time() - 86401);
        self::assertSame(2, $issue->getAge());

        $issue->setState($final);
        self::assertSame(2, $issue->getAge());

        $this->setProperty($issue, 'closedAt', time() - 86401);
        self::assertSame(0, $issue->getAge());
    }

    /**
     * @covers ::getChangedAt
     * @covers ::touch
     */
    public function testChangedAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());

        $timestamp = $this->getProperty($issue, 'createdAt');
        self::assertSame($timestamp, $issue->getChangedAt());

        $this->setProperty($issue, 'changedAt', 0);
        self::assertGreaterThan(2, time() - $issue->getChangedAt());

        $issue->touch();
        self::assertLessThanOrEqual(2, time() - $issue->getChangedAt());
    }

    /**
     * @covers ::getClosedAt
     * @covers ::isClosed
     */
    public function testClosedAt(): void
    {
        $template = new Template(new Project());
        $initial  = new State($template, StateTypeEnum::Initial);
        $final    = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($initial);
        $states->add($final);

        $issue = new Issue($template, new User());

        self::assertNull($issue->getClosedAt());
        self::assertFalse($issue->isClosed());

        $issue->setState($final);

        self::assertLessThanOrEqual(2, time() - $issue->getClosedAt());
        self::assertTrue($issue->isClosed());
    }

    /**
     * @covers ::isCritical
     */
    public function testIsCritical(): void
    {
        $template = new Template(new Project());
        $initial  = new State($template, StateTypeEnum::Initial);
        $final    = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($initial);
        $states->add($final);

        $issue = new Issue($template, new User());

        $template->setCriticalAge(1);
        self::assertFalse($issue->isCritical());

        $this->setProperty($issue, 'createdAt', time() - 86401);
        self::assertTrue($issue->isCritical());

        $issue->setState($final);
        self::assertFalse($issue->isCritical());
    }

    /**
     * @covers ::isFrozen
     */
    public function testIsFrozen(): void
    {
        $template = new Template(new Project());
        $initial  = new State($template, StateTypeEnum::Initial);
        $final    = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($initial);
        $states->add($final);

        $issue = new Issue($template, new User());

        $template->setFrozenTime(1);
        self::assertFalse($issue->isFrozen());

        $issue->setState($final);
        $this->setProperty($issue, 'closedAt', time() - 86401);
        self::assertTrue($issue->isFrozen());

        $template->setFrozenTime(null);
        self::assertFalse($issue->isFrozen());
    }

    /**
     * @covers ::getResumesAt
     * @covers ::isSuspended
     * @covers ::resume
     * @covers ::suspend
     */
    public function testResumesAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());
        self::assertFalse($issue->isSuspended());

        $timestamp = time() + 86400;
        $issue->suspend($timestamp);
        self::assertLessThanOrEqual(2, $timestamp - $issue->getResumesAt());
        self::assertTrue($issue->isSuspended());

        $issue->resume();
        self::assertNull($issue->getResumesAt());
        self::assertFalse($issue->isSuspended());

        $issue->suspend(time());
        self::assertNotNull($issue->getResumesAt());
        self::assertFalse($issue->isSuspended());
    }

    /**
     * @covers ::getEvents
     */
    public function testEvents(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, new User());
        self::assertEmpty($issue->getEvents());

        /** @var \Doctrine\Common\Collections\Collection $events */
        $events = $this->getProperty($issue, 'events');
        $events->add('Event A');
        $events->add('Event B');

        self::assertSame(['Event A', 'Event B'], $issue->getEvents()->getValues());
    }
}
