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
 * @coversDefaultClass \App\Entity\File
 */
final class FileTest extends TestCase
{
    use ReflectionTrait;

    private const UUID_PATTERN = '/^([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})$/';

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
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');

        self::assertMatchesRegularExpression(self::UUID_PATTERN, $file->getUid());
        self::assertSame($event, $file->getEvent());
        self::assertSame('example.csv', $file->getFileName());
        self::assertSame(2309, $file->getFileSize());
        self::assertSame('text/csv', $file->getMimeType());
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

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::IssueEdited);

        new File($event, 'example.csv', 2309, 'text/csv');
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
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');

        $this->setProperty($file, 'id', 1);
        self::assertSame(1, $file->getId());
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

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertSame($event, $file->getEvent());
    }

    /**
     * @covers ::getUid
     */
    public function testUid(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertMatchesRegularExpression(self::UUID_PATTERN, $file->getUid());
    }

    /**
     * @covers ::getFileName
     */
    public function testFileName(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertSame('example.csv', $file->getFileName());
    }

    /**
     * @covers ::getFileSize
     */
    public function testFileSize(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertSame(2309, $file->getFileSize());
    }

    /**
     * @covers ::getMimeType
     */
    public function testMimeType(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertSame('text/csv', $file->getMimeType());
    }

    /**
     * @covers ::isRemoved
     * @covers ::remove
     */
    public function testRemovedAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::FileAttached);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        self::assertFalse($file->isRemoved());

        $file->remove();
        self::assertTrue($file->isRemoved());
    }
}
