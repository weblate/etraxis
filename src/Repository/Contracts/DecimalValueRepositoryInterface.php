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

use App\Entity\DecimalValue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'DecimalValue' entities repository.
 */
interface DecimalValueRepositoryInterface extends ObjectRepository, Selectable, CacheableRepositoryInterface
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(DecimalValue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(DecimalValue $entity, bool $flush = false): void;

    /**
     * Finds specified decimal value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value Decimal value
     */
    public function get(string $value): DecimalValue;
}
