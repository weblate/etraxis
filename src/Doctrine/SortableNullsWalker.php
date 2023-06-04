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

namespace App\Doctrine;

use Doctrine\ORM\Query\SqlWalker;

/**
 * PostgreSQL treats NULLs as greatest values.
 * This walker is to workaround it.
 */
class SortableNullsWalker extends SqlWalker
{
    /**
     * @see SqlWalker::walkOrderByItem
     *
     * @param \Doctrine\ORM\Query\AST\OrderByItem $orderByItem
     */
    public function walkOrderByItem($orderByItem): string
    {
        $sql = parent::walkOrderByItem($orderByItem);

        if ($orderByItem->isAsc()) {
            $sql .= ' NULLS FIRST';
        }

        if ($orderByItem->isDesc()) {
            $sql .= ' NULLS LAST';
        }

        return $sql;
    }
}
