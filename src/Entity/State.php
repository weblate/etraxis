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

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Repository\StateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * State.
 */
#[ORM\Entity(repositoryClass: StateRepository::class)]
#[ORM\Table(name: 'states')]
#[ORM\UniqueConstraint(fields: ['template', 'name'])]
#[Assert\UniqueEntity(fields: ['template', 'name'], message: 'state.conflict.name')]
class State
{
    // Constraints.
    public const MAX_NAME = 50;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Template of the state.
     */
    #[ORM\ManyToOne(inversedBy: 'states')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Template $template;

    /**
     * Name of the state.
     */
    #[ORM\Column(length: 50)]
    protected string $name;

    /**
     * Type of the state (@see StateTypeEnum enum).
     */
    #[ORM\Column(length: 12)]
    protected string $type;

    /**
     * Type of responsibility management (@see StateResponsibleEnum enum).
     */
    #[ORM\Column(length: 10)]
    protected string $responsible;

    /**
     * List of state fields.
     */
    #[ORM\OneToMany(mappedBy: 'state', targetEntity: Field::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $fields;

    /**
     * List of state role transitions.
     */
    #[ORM\OneToMany(mappedBy: 'fromState', targetEntity: StateRoleTransition::class)]
    protected Collection $roleTransitions;

    /**
     * List of state group transitions.
     */
    #[ORM\OneToMany(mappedBy: 'fromState', targetEntity: StateGroupTransition::class)]
    protected Collection $groupTransitions;

    /**
     * List of responsible groups.
     */
    #[ORM\OneToMany(mappedBy: 'state', targetEntity: StateResponsibleGroup::class)]
    protected Collection $responsibleGroups;

    /**
     * Creates new state in the specified template.
     */
    public function __construct(Template $template, StateTypeEnum $type)
    {
        $this->template    = $template;
        $this->type        = $type->value;
        $this->responsible = StateResponsibleEnum::Remove->value;

        $this->fields            = new ArrayCollection();
        $this->roleTransitions   = new ArrayCollection();
        $this->groupTransitions  = new ArrayCollection();
        $this->responsibleGroups = new ArrayCollection();
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info'])]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info'])]
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
    public function getType(): StateTypeEnum
    {
        return StateTypeEnum::from($this->type);
    }

    /**
     * Whether the state is final.
     */
    public function isFinal(): bool
    {
        return StateTypeEnum::Final === $this->getType();
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getResponsible(): StateResponsibleEnum
    {
        return !$this->isFinal()
            ? StateResponsibleEnum::from($this->responsible)
            : StateResponsibleEnum::Remove;
    }

    /**
     * Property setter.
     */
    public function setResponsible(StateResponsibleEnum $responsible): self
    {
        if (!$this->isFinal()) {
            $this->responsible = $responsible->value;
        }

        return $this;
    }

    /**
     * Property getter.
     */
    public function getFields(): Collection
    {
        return $this->fields->filter(fn (Field $field) => !$field->isRemoved());
    }

    /**
     * Property getter.
     */
    public function getRoleTransitions(): Collection
    {
        return $this->roleTransitions;
    }

    /**
     * Property getter.
     */
    public function getGroupTransitions(): Collection
    {
        return $this->groupTransitions;
    }

    /**
     * Property getter.
     */
    public function getResponsibleGroups(): Collection
    {
        return $this->responsibleGroups;
    }
}
