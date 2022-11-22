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

use App\Entity\Change;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'Change' entities repository.
 */
interface ChangeRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(Change $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(Change $entity, bool $flush = false): void;

    /**
     * Finds all changes in the specified issue.
     * Only fields, allowed to be seen by specified user, will be returned.
     *
     * @return Change[]
     */
    public function findAllByIssue(Issue $issue, User $user): array;
}
