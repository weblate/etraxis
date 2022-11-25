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

use App\Entity\ListItem;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified list item.
 */
final class UpdateListItemCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $item,
        private readonly int $value,
        #[Assert\NotBlank]
        #[Assert\Length(max: ListItem::MAX_TEXT)]
        private readonly string $text
    ) {
    }

    /**
     * @return int Item ID
     */
    public function getItem(): int
    {
        return $this->item;
    }

    /**
     * @return int New value of the item
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return string New text of the item
     */
    public function getText(): string
    {
        return $this->text;
    }
}
