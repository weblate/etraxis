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

use App\Repository\WatcherRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Issue watcher.
 */
#[ORM\Entity(repositoryClass: WatcherRepository::class)]
#[ORM\Table(name: 'watchers')]
class Watcher
{
    /**
     * Watched issue.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'watchers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Issue $issue;

    /**
     * Watching user.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    /**
     * Creates new watcher.
     */
    public function __construct(Issue $issue, User $user)
    {
        $this->issue = $issue;
        $this->user  = $user;
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
}
