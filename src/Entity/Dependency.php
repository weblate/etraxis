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
use App\Repository\DependencyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Issue dependency.
 */
#[ORM\Entity(repositoryClass: DependencyRepository::class)]
#[ORM\Table(name: 'dependencies')]
#[ORM\UniqueConstraint(fields: ['event'])]
class Dependency
{
    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Event of the dependency (owned by the dependant issue).
     */
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Event $event;

    /**
     * Dependency issue.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected Issue $issue;

    /**
     * Creates new dependency.
     */
    public function __construct(Event $event, Issue $issue)
    {
        if (EventTypeEnum::DependencyAdded !== $event->getType()) {
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
