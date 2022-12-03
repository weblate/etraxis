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
use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * Attached file.
 */
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: 'files')]
#[ORM\UniqueConstraint(fields: ['event'])]
#[ORM\UniqueConstraint(fields: ['uid'])]
class File
{
    // A fallback MIME type.
    public const DEFAULT_MIMETYPE = 'application/octet-stream';

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the file.
     */
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * Unique UID for storage.
     */
    #[ORM\Column(length: 36)]
    protected string $uid;

    /**
     * File name.
     */
    #[ORM\Column(length: 100)]
    protected string $fileName;

    /**
     * File size.
     */
    #[ORM\Column]
    protected int $fileSize;

    /**
     * MIME type.
     */
    #[ORM\Column(length: 255)]
    protected string $mimeType;

    /**
     * Unix Epoch timestamp when the fili was removed (soft-deleted).
     */
    #[ORM\Column(nullable: true)]
    protected ?int $removedAt = null;

    /**
     * Creates new file.
     */
    public function __construct(Event $event, string $name, int $size, string $type)
    {
        if (EventTypeEnum::FileAttached !== $event->getType()) {
            throw new \UnexpectedValueException('Invalid event: '.$event->getType()->name);
        }

        $this->uid = Uuid::v4()->toRfc4122();

        $this->event    = $event;
        $this->fileName = $name;
        $this->fileSize = $size;
        $this->mimeType = $type;
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
     * Returns user attached the file.
     */
    #[Groups('info')]
    public function getUser(): User
    {
        return $this->event->getUser();
    }

    /**
     * Returns timestamp when the file was attached.
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
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Whether the file is removed (soft-deleted).
     */
    public function isRemoved(): bool
    {
        return null !== $this->removedAt;
    }

    /**
     * Marks file as removed (soft-deleted).
     */
    public function remove(): void
    {
        if (null === $this->removedAt) {
            $this->removedAt = time();
        }
    }
}
