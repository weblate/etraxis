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

use App\Entity\Field;
use App\Entity\ListItem;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'ListItem' entities repository.
 */
interface ListItemRepositoryInterface extends ObjectRepository, Selectable, CacheableRepositoryInterface
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(ListItem $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(ListItem $entity, bool $flush = false): void;

    /**
     * Finds all list items of the specified field.
     *
     * @return ListItem[]
     */
    public function findAllByField(Field $field): array;

    /**
     * Finds list item by its field and value.
     */
    public function findOneByValue(Field $field, int $value): ?ListItem;
}
