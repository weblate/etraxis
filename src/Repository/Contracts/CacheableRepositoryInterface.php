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

use Doctrine\Persistence\ObjectRepository;

/**
 * Interface for a repository with a built-in cache.
 */
interface CacheableRepositoryInterface extends ObjectRepository
{
    /**
     * Retrieves from the repository all entities specified by their IDs, and stores them in the memory cache.
     *
     * @param int[] $ids List of IDs
     *
     * @return int Number of entities pushed to the cache
     */
    public function warmup(array $ids): int;
}
