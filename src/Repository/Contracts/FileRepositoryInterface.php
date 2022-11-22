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

use App\Entity\File;
use App\Entity\Issue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'File' entities repository.
 */
interface FileRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(File $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(File $entity, bool $flush = false): void;

    /**
     * Finds all files currently attached to the specified issue.
     *
     * @return File[]
     */
    public function findAllByIssue(Issue $issue): array;

    /**
     * Returns absolute path including filename to the specified attachment.
     */
    public function getFullPath(File $file): string;
}
