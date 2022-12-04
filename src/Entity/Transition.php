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
use App\Repository\TransitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Issue transition to another state.
 */
#[ORM\Entity(repositoryClass: TransitionRepository::class)]
#[ORM\Table(name: 'transitions')]
#[ORM\UniqueConstraint(fields: ['event'])]
class Transition implements \Stringable
{
    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the transition.
     */
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * New state.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected State $state;

    /**
     * List of field values.
     */
    #[ORM\OneToMany(mappedBy: 'transition', targetEntity: FieldValue::class)]
    protected Collection $values;

    /**
     * Creates new transition.
     */
    public function __construct(Event $event, State $state)
    {
        $supported = [
            EventTypeEnum::IssueClosed,
            EventTypeEnum::IssueCreated,
            EventTypeEnum::IssueReopened,
            EventTypeEnum::StateChanged,
        ];

        if (!in_array($event->getType(), $supported, true)) {
            throw new \UnexpectedValueException('Invalid event: '.$event->getType()->name);
        }

        if ($event->getIssue()->getTemplate() !== $state->getTemplate()) {
            throw new \UnexpectedValueException('Unknown state: '.$state->getName());
        }

        $this->event = $event;
        $this->state = $state;

        $this->values = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->id;
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
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getValues(): Collection
    {
        return $this->values;
    }
}
