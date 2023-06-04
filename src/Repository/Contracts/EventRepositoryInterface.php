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

namespace App\Repository\Contracts;

use App\Entity\Event;
use App\Entity\Issue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'Event' entities repository.
 */
interface EventRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist
     */
    public function persist(Event $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove
     */
    public function remove(Event $entity, bool $flush = false): void;

    /**
     * Finds all events of the specified issue.
     *
     * @param Issue $issue               Target issue
     * @param bool  $hidePrivateComments Whether to remove private commenting from the list
     *
     * @return Event[]
     */
    public function findAllByIssue(Issue $issue, bool $hidePrivateComments): array;
}
