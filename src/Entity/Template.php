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

use App\Entity\Enums\StateTypeEnum;
use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Template.
 */
#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\Table(name: 'templates')]
#[ORM\UniqueConstraint(fields: ['project', 'name'])]
#[ORM\UniqueConstraint(fields: ['project', 'prefix'])]
#[Assert\UniqueEntity(fields: ['project', 'name'], message: 'template.conflict.name')]
#[Assert\UniqueEntity(fields: ['project', 'prefix'], message: 'template.conflict.prefix')]
class Template
{
    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_PREFIX      = 5;
    public const MAX_DESCRIPTION = 100;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Project of the template.
     */
    #[ORM\ManyToOne(inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Project $project;

    /**
     * Name of the template.
     */
    #[ORM\Column(length: 50)]
    protected string $name;

    /**
     * Prefix of the template (used as a prefix in ID of issues, created using this template).
     */
    #[ORM\Column(length: 5)]
    protected string $prefix;

    /**
     * Optional description of the template.
     */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $description = null;

    /**
     * Whether the template is locked for edition.
     */
    #[ORM\Column]
    protected bool $locked;

    /**
     * When an issue remains opened for more than this amount of days it's displayed in red in the list of issues.
     */
    #[ORM\Column(nullable: true)]
    protected ?int $criticalAge = null;

    /**
     * When an issue is closed a user cannot change its state anymore, but one still can edit its fields, add comments
     * and attach files. If frozen time is specified it will be allowed to edit the issue this amount of days after its
     * closure. After that the issue becomes read-only. If this attribute is not specified, an issue will never become
     * read-only.
     */
    #[ORM\Column(nullable: true)]
    protected ?int $frozenTime = null;

    /**
     * List of template states.
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: State::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected Collection $states;

    /**
     * List of template role permissions.
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateRolePermission::class)]
    protected Collection $rolePermissions;

    /**
     * List of template group permissions.
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateGroupPermission::class)]
    protected Collection $groupPermissions;

    /**
     * Creates new template in the specified project.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->locked  = true;

        $this->states           = new ArrayCollection();
        $this->rolePermissions  = new ArrayCollection();
        $this->groupPermissions = new ArrayCollection();
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
    public function getProject(): Project
    {
        return $this->project;
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
    #[Groups(['api', 'info'])]
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Property setter.
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info'])]
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
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Property setter.
     */
    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getCriticalAge(): ?int
    {
        return $this->criticalAge;
    }

    /**
     * Property setter.
     */
    public function setCriticalAge(?int $criticalAge): self
    {
        $this->criticalAge = $criticalAge;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getFrozenTime(): ?int
    {
        return $this->frozenTime;
    }

    /**
     * Property setter.
     */
    public function setFrozenTime(?int $frozenTime): self
    {
        $this->frozenTime = $frozenTime;

        return $this;
    }

    /**
     * Returns initial state of the template if present.
     */
    public function getInitialState(): ?State
    {
        return $this->states->filter(fn (State $state) => StateTypeEnum::Initial === $state->getType())->first() ?: null;
    }

    /**
     * Property getter.
     */
    public function getStates(): Collection
    {
        return $this->states;
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
