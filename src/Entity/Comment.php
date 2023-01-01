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
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Issue comment.
 */
#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
#[ORM\UniqueConstraint(fields: ['event'])]
class Comment
{
    // Constraints.
    public const MAX_BODY = 10000;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the comment.
     */
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * Comment's body.
     */
    #[ORM\Column(length: 10000)]
    protected string $body;

    /**
     * Whether the comment is private.
     */
    #[ORM\Column]
    protected bool $private;

    /**
     * Creates new comment.
     */
    public function __construct(Event $event)
    {
        if (!in_array($event->getType(), [EventTypeEnum::PublicComment, EventTypeEnum::PrivateComment], true)) {
            throw new \UnexpectedValueException('Invalid event: '.$event->getType()->name);
        }

        $this->event   = $event;
        $this->private = EventTypeEnum::PrivateComment === $event->getType();
    }

    /**
     * Property getter.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Returns author of the comment.
     */
    #[Groups('info')]
    public function getUser(): User
    {
        return $this->event->getUser();
    }

    /**
     * Returns comment creation time.
     */
    #[Groups('info')]
    public function getCreatedAt(): int
    {
        return $this->event->getCreatedAt();
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Property setter.
     */
    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * Property setter.
     */
    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }
}
