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

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Group.
 */
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'groups')]
#[ORM\UniqueConstraint(fields: ['project', 'name'])]
class Group
{
    // Constraints.
    public const MAX_NAME        = 25;
    public const MAX_DESCRIPTION = 100;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Project of the group.
     */
    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected ?Project $project;

    /**
     * Name of the group.
     */
    #[ORM\Column(length: 25)]
    protected string $name;

    /**
     * Optional description of the group.
     */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $description = null;

    /**
     * List of members.
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'groups')]
    #[ORM\JoinTable(name: 'membership')]
    protected Collection $members;

    /**
     * Creates new group in the specified project (NULL creates a global group).
     */
    public function __construct(?Project $project = null)
    {
        $this->project = $project;
        $this->members = new ArrayCollection();
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
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Whether the group is global.
     */
    public function isGlobal(): bool
    {
        return null === $this->project;
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
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Adds user to the group.
     */
    public function addMember(User $user): self
    {
        if (!$this->members->contains($user)) {
            $this->members[] = $user;
        }

        return $this;
    }

    /**
     * Removes user from the group.
     */
    public function removeMember(User $user): self
    {
        $this->members->removeElement($user);

        return $this;
    }
}
