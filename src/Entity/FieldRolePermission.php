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
use App\Entity\Enums\SystemRoleEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * Field permission for system role.
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_role_permissions')]
class FieldRolePermission
{
    /**
     * Field.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'rolePermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Field $field;

    /**
     * System role.
     */
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $role;

    /**
     * Permission granted to the role for this field.
     */
    #[ORM\Id]
    #[ORM\Column(length: 10)]
    protected string $permission;

    /**
     * Constructor.
     */
    public function __construct(Field $field, SystemRoleEnum $role, FieldPermissionEnum $permission)
    {
        $this->field      = $field;
        $this->role       = $role->value;
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
    public function getRole(): SystemRoleEnum
    {
        return SystemRoleEnum::from($this->role);
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
