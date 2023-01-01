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

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Repository with a built-in memory cache.
 */
abstract class AbstractCacheableRepository extends ServiceEntityRepository implements Contracts\CacheableRepositoryInterface
{
    protected array $cache = [];

    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        if (null === $id) {
            return null;
        }

        if (!array_key_exists($id, $this->cache)) {
            $entity = parent::find($id, $lockMode, $lockVersion);

            if ($entity) {
                $this->cache[$id] = $entity;
            }
        }

        return $this->cache[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function warmup(array $ids): int
    {
        $this->cache = [];

        $entities = $this->findBy(['id' => $ids]);

        foreach ($entities as $entity) {
            $this->cache[$entity->getId()] = $entity;
        }

        return count($this->cache);
    }
}
