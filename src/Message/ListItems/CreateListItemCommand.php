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

/**
 * Creates new list item.
 */
final class CreateListItemCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        private readonly int $value,
        private readonly string $text
    ) {
    }

    /**
     * @return int ID of the item's field
     */
    public function getField(): int
    {
        return $this->field;
    }

    /**
     * @return int Value of the item
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return string Text of the item
     */
    public function getText(): string
    {
        return $this->text;
    }
}
