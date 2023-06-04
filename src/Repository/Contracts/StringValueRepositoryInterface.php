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

use App\Entity\StringValue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'StringValue' entities repository.
 */
interface StringValueRepositoryInterface extends ObjectRepository, Selectable, CacheableRepositoryInterface
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist
     */
    public function persist(StringValue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove
     */
    public function remove(StringValue $entity, bool $flush = false): void;

    /**
     * Finds specified string value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value String value
     */
    public function get(string $value): StringValue;
}
