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

namespace App\Repository;

use App\Entity\Field;
use App\Entity\ListItem;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'ListItem' entities repository.
 */
class ListItemRepository extends AbstractCacheableRepository implements Contracts\ListItemRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListItem::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(ListItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(ListItem $entity, bool $flush = false): void
    {
        unset($this->cache[$entity->getId()]);

        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByField(Field $field): array
    {
        return $this->findBy([
            'field' => $field,
        ], [
            'value' => 'ASC',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByValue(Field $field, int $value): ?ListItem
    {
        return $this->findOneBy([
            'field' => $field,
            'value' => $value,
        ]);
    }
}
