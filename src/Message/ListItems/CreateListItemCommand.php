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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new list item.
 */
final class CreateListItemCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        #[Groups('api')]
        private readonly int $value,
        #[Assert\NotBlank]
        #[Assert\Length(max: ListItem::MAX_TEXT)]
        #[Groups('api')]
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
