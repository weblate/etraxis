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

use App\Entity\Enums\FieldPermissionEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * Field permission for group.
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_group_permissions')]
class FieldGroupPermission
{
    /**
     * Field.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'groupPermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Field $field;

    /**
     * Group.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Group $group;

    /**
     * Permission granted to the group for this field.
     */
    #[ORM\Id]
    #[ORM\Column(length: 10)]
    protected string $permission;

    /**
     * Constructor.
     */
    public function __construct(Field $field, Group $group, FieldPermissionEnum $permission)
    {
        $this->field      = $field;
        $this->group      = $group;
        $this->permission = $permission->value;
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
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * Property getter.
     */
    public function getPermission(): FieldPermissionEnum
    {
        return FieldPermissionEnum::from($this->permission);
    }

    /**
     * Property setter.
     */
    public function setPermission(FieldPermissionEnum $permission): self
    {
        $this->permission = $permission->value;

        return $this;
    }
}
