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

namespace App\Entity;

use App\Entity\Enums\FieldTypeEnum;
use App\Repository\ListItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * List item.
 */
#[ORM\Entity(repositoryClass: ListItemRepository::class)]
#[ORM\Table(name: 'list_items')]
#[ORM\UniqueConstraint(fields: ['field', 'itemValue'])]
#[ORM\UniqueConstraint(fields: ['field', 'itemText'])]
class ListItem
{
    // Constraints.
    public const MAX_TEXT = 50;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Field.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Field $field;

    /**
     * Value of the item.
     */
    #[ORM\Column]
    protected int $itemValue;

    /**
     * Text of the item.
     */
    #[ORM\Column(length: 50)]
    protected string $itemText;

    /**
     * Adds new item to specified field of "List" type.
     */
    public function __construct(Field $field)
    {
        if (FieldTypeEnum::List !== $field->getType()) {
            throw new \UnexpectedValueException('Invalid field type: '.$field->getType()->name);
        }

        $this->field = $field;
    }

    /**
     * Property getter.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * Property getter.
     */
    public function getItemValue(): int
    {
        return $this->itemValue;
    }

    /**
     * Property setter.
     */
    public function setItemValue(int $itemValue): self
    {
        $this->itemValue = $itemValue;

        return $this;
    }

    /**
     * Property getter.
     */
    public function getItemText(): string
    {
        return $this->itemText;
    }

    /**
     * Property setter.
     */
    public function setItemText(string $itemText): self
    {
        $this->itemText = $itemText;

        return $this;
    }
}
