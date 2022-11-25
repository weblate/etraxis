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

namespace App\Message\ListItems;

use App\MessageBus\Contracts\CommandInterface;

/**
 * Deletes specified list item.
 */
final class DeleteListItemCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $item)
    {
    }

    /**
     * @return int Item ID
     */
    public function getItem(): int
    {
        return $this->item;
    }
}
