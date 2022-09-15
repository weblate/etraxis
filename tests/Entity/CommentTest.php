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
 * @coversDefaultClass \App\Entity\Comment
 */
final class CommentTest extends TestCase
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

        $event   = new Event($issue, $user, EventTypeEnum::PublicComment);
        $comment = new Comment($event);

        self::assertSame($event, $comment->getEvent());
        self::assertFalse($comment->isPrivate());

        $event   = new Event($issue, $user, EventTypeEnum::PrivateComment);
        $comment = new Comment($event);

        self::assertSame($event, $comment->getEvent());
        self::assertTrue($comment->isPrivate());
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

        new Comment($event);
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
        $event = new Event($issue, $user, EventTypeEnum::PublicComment);

        $comment = new Comment($event);

        $this->setProperty($comment, 'id', 1);
        self::assertSame(1, $comment->getId());
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
        $event = new Event($issue, $user, EventTypeEnum::PublicComment);

        $comment = new Comment($event);
        self::assertSame($event, $comment->getEvent());
    }

    /**
     * @covers ::getBody
     * @covers ::setBody
     */
    public function testBody(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::PublicComment);

        $comment = new Comment($event);

        $comment->setBody('Lorem Ipsum');
        self::assertSame('Lorem Ipsum', $comment->getBody());
    }

    /**
     * @covers ::isPrivate
     * @covers ::setPrivate
     */
    public function testPrivate(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);
        $event = new Event($issue, $user, EventTypeEnum::PublicComment);

        $comment = new Comment($event);
        self::assertFalse($comment->isPrivate());

        $comment->setPrivate(true);
        self::assertTrue($comment->isPrivate());

        $comment->setPrivate(false);
        self::assertFalse($comment->isPrivate());
    }
}
