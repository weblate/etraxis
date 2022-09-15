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
 * @coversDefaultClass \App\Entity\Dependency
 */
final class DependencyTest extends TestCase
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

        $issue  = new Issue($template, $user);
        $issue2 = new Issue($template, $user);
        $event  = new Event($issue, $user, EventTypeEnum::DependencyAdded);

        $dependency = new Dependency($event, $issue2);

        self::assertSame($event, $dependency->getEvent());
        self::assertSame($issue2, $dependency->getIssue());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid event: IssueEdited');

        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue  = new Issue($template, $user);
        $issue2 = new Issue($template, $user);
        $event  = new Event($issue, $user, EventTypeEnum::IssueEdited);

        new Dependency($event, $issue2);
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

        $issue  = new Issue($template, $user);
        $issue2 = new Issue($template, $user);
        $event  = new Event($issue, $user, EventTypeEnum::DependencyAdded);

        $dependency = new Dependency($event, $issue2);

        $this->setProperty($dependency, 'id', 1);
        self::assertSame(1, $dependency->getId());
    }

    /**
     * @covers ::getEvent
     */
    public function testEvent(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue  = new Issue($template, $user);
        $issue2 = new Issue($template, $user);
        $event  = new Event($issue, $user, EventTypeEnum::DependencyAdded);

        $dependency = new Dependency($event, $issue2);

        self::assertSame($event, $dependency->getEvent());
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

        $issue  = new Issue($template, $user);
        $issue2 = new Issue($template, $user);
        $event  = new Event($issue, $user, EventTypeEnum::DependencyAdded);

        $dependency = new Dependency($event, $issue2);

        self::assertSame($issue2, $dependency->getIssue());
    }
}
