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

use App\Repository\LastReadRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Issue last read.
 */
#[ORM\Entity(repositoryClass: LastReadRepository::class)]
#[ORM\Table(name: 'last_reads')]
class LastRead
{
    /**
     * Issue.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Issue $issue;

    /**
     * User.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    /**
     * Unix Epoch timestamp when the issue has been viewed by the user last time.
     */
    #[ORM\Column]
    protected int $readAt;

    /**
     * Creates new read.
     */
    public function __construct(Issue $issue, User $user)
    {
        $this->issue  = $issue;
        $this->user   = $user;
        $this->readAt = time();
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
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Property getter.
     */
    public function getReadAt(): int
    {
        return $this->readAt;
    }

    /**
     * Updates the timestamp of when the issue has been read.
     */
    public function touch(): self
    {
        $this->readAt = time();

        return $this;
    }
}
