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

namespace App\Repository\Contracts;

use App\Entity\Comment;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'Comment' entities repository.
 */
interface CommentRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(Comment $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(Comment $entity, bool $flush = false): void;
}