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
use App\Repository\RelatedIssueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Related issue.
 */
#[ORM\Entity(repositoryClass: RelatedIssueRepository::class)]
#[ORM\Table(name: 'related_issues')]
#[ORM\UniqueConstraint(fields: ['event'])]
class RelatedIssue
{
    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the reference (owned by the referring issue).
     */
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * Related issue.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected Issue $issue;

    /**
     * Creates new reference.
     */
    public function __construct(Event $event, Issue $issue)
    {
        if (EventTypeEnum::RelatedIssueAdded !== $event->getType()) {
            throw new \UnexpectedValueException('Invalid event: '.$event->getType()->name);
        }

        $this->event = $event;
        $this->issue = $issue;
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
     * Property getter.
     */
    public function getIssue(): Issue
    {
        return $this->issue;
    }
}
