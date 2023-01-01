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

namespace App\Entity;

use App\Entity\Enums\FieldTypeEnum;
use App\Repository\ListItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * List item.
 */
#[ORM\Entity(repositoryClass: ListItemRepository::class)]
#[ORM\Table(name: 'list_items')]
#[ORM\UniqueConstraint(fields: ['field', 'value'])]
#[ORM\UniqueConstraint(fields: ['field', 'text'])]
#[Assert\UniqueEntity(fields: ['field', 'value'], message: 'listitem.conflict.value')]
#[Assert\UniqueEntity(fields: ['field', 'text'], message: 'listitem.conflict.text')]
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
    protected int $value;

    /**
     * Text of the item.
     */
    #[ORM\Column(length: 50)]
    protected string $text;

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
    #[Groups(['api', 'info'])]
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Property setter.
     */
    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info'])]
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Property setter.
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
}
