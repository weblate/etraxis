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
use App\Repository\ChangeRepository;
use App\Utils\OpenApiInterface;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Field value change.
 */
#[ORM\Entity(repositoryClass: ChangeRepository::class)]
#[ORM\Table(name: 'changes')]
#[ORM\UniqueConstraint(fields: ['event', 'field'])]
class Change
{
    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the change.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * Changed field (NULL for the issue's subject).
     */
    #[ORM\ManyToOne]
    protected ?Field $field = null;

    /**
     * Old value.
     *
     * @see FieldValue::$value property
     */
    #[ORM\Column(nullable: true)]
    protected ?int $oldValue = null;

    /**
     * New value.
     *
     * @see FieldValue::$value property
     */
    #[ORM\Column(nullable: true)]
    protected ?int $newValue = null;

    /**
     * Creates new change.
     */
    public function __construct(Event $event, ?Field $field, ?int $oldValue, ?int $newValue)
    {
        if (EventTypeEnum::IssueEdited !== $event->getType()) {
            throw new \UnexpectedValueException('Invalid event: '.$event->getType()->name);
        }

        if (null !== $field && $event->getIssue()->getTemplate() !== $field->getState()->getTemplate()) {
            throw new \UnexpectedValueException('Unknown field: '.$field->getName());
        }

        $this->event    = $event;
        $this->field    = $field;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
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
     * Returns author of the change.
     */
    #[Groups('info')]
    public function getUser(): User
    {
        return $this->event->getUser();
    }

    /**
     * Returns time of the change.
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
    public function getField(): ?Field
    {
        return $this->field;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, oneOf: [
        new API\Schema(type: OpenApiInterface::TYPE_BOOLEAN),
        new API\Schema(type: OpenApiInterface::TYPE_INTEGER),
        new API\Schema(type: OpenApiInterface::TYPE_STRING),
        new API\Schema(ref: new Model(type: ListItem::class)),
    ])]
    public function getOldValue(): ?int
    {
        return $this->oldValue;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, oneOf: [
        new API\Schema(type: OpenApiInterface::TYPE_BOOLEAN),
        new API\Schema(type: OpenApiInterface::TYPE_INTEGER),
        new API\Schema(type: OpenApiInterface::TYPE_STRING),
        new API\Schema(ref: new Model(type: ListItem::class)),
    ])]
    public function getNewValue(): ?int
    {
        return $this->newValue;
    }
}
