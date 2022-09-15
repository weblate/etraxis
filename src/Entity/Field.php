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
use App\Repository\FieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Field.
 */
#[ORM\Entity(repositoryClass: FieldRepository::class)]
#[ORM\Table(name: 'fields')]
#[ORM\UniqueConstraint(fields: ['state', 'name', 'removedAt'])]
#[ORM\UniqueConstraint(fields: ['state', 'position', 'removedAt'])]
class Field
{
    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_DESCRIPTION = 1000;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * State of the field.
     */
    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected State $state;

    /**
     * Name of the field.
     */
    #[ORM\Column(length: 50)]
    protected string $name;

    /**
     * Type of the field (@see FieldTypeEnum enum).
     */
    #[ORM\Column(length: 10)]
    protected string $type;

    /**
     * Optional description of the field.
     */
    #[ORM\Column(length: 1000, nullable: true)]
    protected ?string $description = null;

    /**
     * Ordinal number of the field.
     * No duplicates of this number among fields of the same state are allowed.
     */
    #[ORM\Column]
    protected int $position;

    /**
     * Whether the field is required.
     */
    #[ORM\Column]
    protected bool $required;

    /**
     * Unix Epoch timestamp when the field was removed (soft-deleted).
     */
    #[ORM\Column(nullable: true)]
    protected ?int $removedAt = null;

    /**
     * Field parameters.
     */
    #[ORM\Column(nullable: true)]
    protected array $parameters = [];

    /**
     * List of field role permissions.
     */
    #[ORM\OneToMany(mappedBy: 'field', targetEntity: FieldRolePermission::class)]
    protected Collection $rolePermissions;

    /**
     * List of field group permissions.
     */
    #[ORM\OneToMany(mappedBy: 'field', targetEntity: FieldGroupPermission::class)]
    protected Collection $groupPermissions;

    /**
     * Creates new field for the specified state.
     */
    public function __construct(State $state, FieldTypeEnum $type)
    {
        $this->state = $state;
        $this->type  = $type->value;

        $this->rolePermissions  = new ArrayCollection();
        $this->groupPermissions = new ArrayCollection();
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
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Property getter.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Property setter.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Property getter.
     */
    public function getType(): FieldTypeEnum
    {
        return FieldTypeEnum::from($this->type);
    }

    /**
     * Property getter.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Property setter.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Property getter.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Property setter.
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Property getter.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Property setter.
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Whether the field is removed (soft-deleted).
     */
    public function isRemoved(): bool
    {
        return null !== $this->removedAt;
    }

    /**
     * Marks field as removed (soft-deleted).
     */
    public function remove(): self
    {
        if (null === $this->removedAt) {
            $this->removedAt = time();
        }

        return $this;
    }

    /**
     * Property getter.
     */
    public function getAllParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Property getter.
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        return $this->parameters[$parameter] ?? null;
    }

    /**
     * Property setter.
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        if (null === $value) {
            unset($this->parameters[$parameter]);
        } else {
            $this->parameters[$parameter] = $value;
        }

        return $this;
    }

    /**
     * Property getter.
     */
    public function getRolePermissions(): Collection
    {
        return $this->rolePermissions;
    }

    /**
     * Property getter.
     */
    public function getGroupPermissions(): Collection
    {
        return $this->groupPermissions;
    }
}
