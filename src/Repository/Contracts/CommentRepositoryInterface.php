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

use App\Entity\Comment;
use App\Entity\Issue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'Comment' entities repository.
 */
interface CommentRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist
     */
    public function persist(Comment $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove
     */
    public function remove(Comment $entity, bool $flush = false): void;

    /**
     * Finds all comments of the specified issue.
     *
     * @param Issue $issue               Target issue
     * @param bool  $hidePrivateComments Whether to remove private commenting from the list
     *
     * @return Comment[]
     */
    public function findAllByIssue(Issue $issue, bool $hidePrivateComments): array;
}
