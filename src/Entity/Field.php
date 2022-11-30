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

use App\Controller\ApiControllerInterface;
use App\Entity\Enums\FieldTypeEnum;
use App\Repository\FieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Field.
 */
#[ORM\Entity(repositoryClass: FieldRepository::class)]
#[ORM\Table(name: 'fields')]
#[ORM\UniqueConstraint(fields: ['state', 'name', 'removedAt'])]
#[ORM\UniqueConstraint(fields: ['state', 'position', 'removedAt'])]
#[Assert\UniqueEntity(fields: ['state', 'name', 'removedAt'], message: 'field.conflict.name', ignoreNull: false)]
class Field
{
    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_DESCRIPTION = 1000;
    public const MAX_PCRE        = 500;

    // Field parameters.
    public const DEFAULT      = 'default';
    public const LENGTH       = 'length';
    public const MINIMUM      = 'minimum';
    public const MAXIMUM      = 'maximum';
    public const PCRE_CHECK   = 'pcre-check';
    public const PCRE_SEARCH  = 'pcre-search';
    public const PCRE_REPLACE = 'pcre-replace';

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
    #[Groups('api')]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
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
    #[Groups('api')]
    public function getType(): FieldTypeEnum
    {
        return FieldTypeEnum::from($this->type);
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
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
    #[Groups('api')]
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
    #[Groups('api')]
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
    #[Groups('api')]
    #[API\Property(property: 'parameters', type: ApiControllerInterface::TYPE_OBJECT, oneOf: [
        new API\Schema(ref: new Model(type: FieldStrategy\CheckboxStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\DateStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\DecimalStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\DurationStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\IssueStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\ListStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\NumberStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\StringStrategy::class)),
        new API\Schema(ref: new Model(type: FieldStrategy\TextStrategy::class)),
    ])]
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

    /**
     * Returns strategy for this field.
     */
    public function getStrategy(): FieldStrategy\FieldStrategyInterface
    {
        return match ($this->getType()) {
            FieldTypeEnum::Checkbox => new FieldStrategy\CheckboxStrategy($this),
            FieldTypeEnum::Date     => new FieldStrategy\DateStrategy($this),
            FieldTypeEnum::Decimal  => new FieldStrategy\DecimalStrategy($this),
            FieldTypeEnum::Duration => new FieldStrategy\DurationStrategy($this),
            FieldTypeEnum::Issue    => new FieldStrategy\IssueStrategy($this),
            FieldTypeEnum::List     => new FieldStrategy\ListStrategy($this),
            FieldTypeEnum::Number   => new FieldStrategy\NumberStrategy($this),
            FieldTypeEnum::String   => new FieldStrategy\StringStrategy($this),
            FieldTypeEnum::Text     => new FieldStrategy\TextStrategy($this),
        };
    }
}
