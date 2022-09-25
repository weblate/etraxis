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

use App\Entity\TextValue;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'TextValue' entities repository.
 */
interface TextValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(TextValue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(TextValue $entity, bool $flush = false): void;

    /**
     * Finds specified text value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value Text value
     */
    public function get(string $value): TextValue;
}
