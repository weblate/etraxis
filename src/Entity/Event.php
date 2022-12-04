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
use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Event.
 */
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    // Constraints.
    public const MAX_PARAMETER = 100;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Issue of the event.
     */
    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Issue $issue;

    /**
     * Initiator of the event.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected User $user;

    /**
     * Type of the event (@see EventTypeEnum enum).
     */
    #[ORM\Column(length: 20)]
    protected string $type;

    /**
     * Unix Epoch timestamp when the event has happened.
     */
    #[ORM\Column]
    protected int $createdAt;

    /**
     * Event parameter.
     *
     * Depends on the event type as following (@see EventTypeEnum enum):
     *     IssueCreated        - Initial state (state name)
     *     IssueEdited         - NULL (not used)
     *     StateChanged        - New state (state name)
     *     IssueReopened       - New state of the reopened issue (state name)
     *     IssueClosed         - New state of the closed issue (state name)
     *     IssueAssigned       - Responsible user (user's full name)
     *     IssueReassigned     - Responsible user (user's full name)
     *     IssueSuspended      - Unix Epoch timestamp when the issue was supposed to resume
     *     IssueResumed        - NULL (not used)
     *     PublicComment       - NULL (not used)
     *     PrivateComment      - NULL (not used)
     *     FileAttached        - Attached file (name of the attachment)
     *     FileDeleted         - Deleted file (name of the attachment)
     *     DependencyAdded     - Dependency issue (issue reference)
     *     DependencyRemoved   - Dependency issue (issue reference)
     *     RelatedIssueAdded   - Related issue (issue reference)
     *     RelatedIssueRemoved - Related issue (issue reference)
     */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $parameter;

    /**
     * Creates new event.
     */
    public function __construct(Issue $issue, User $user, EventTypeEnum $type, ?string $parameter = null)
    {
        $this->issue     = $issue;
        $this->user      = $user;
        $this->type      = $type->value;
        $this->createdAt = EventTypeEnum::IssueCreated === $type ? $issue->getCreatedAt() : time();
        $this->parameter = $parameter;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    public function getIssue(): Issue
    {
        return $this->issue;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getType(): EventTypeEnum
    {
        return EventTypeEnum::from($this->type);
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getParameter(): ?string
    {
        return $this->parameter;
    }
}
