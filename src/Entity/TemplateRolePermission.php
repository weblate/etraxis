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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Template permission for system role.
 */
#[ORM\Entity]
#[ORM\Table(name: 'template_role_permissions')]
class TemplateRolePermission
{
    /**
     * Template.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'rolePermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Template $template;

    /**
     * System role.
     */
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $role;

    /**
     * Permission granted to the role for this template.
     */
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $permission;

    /**
     * Constructor.
     */
    public function __construct(Template $template, SystemRoleEnum $role, TemplatePermissionEnum $permission)
    {
        $this->template   = $template;
        $this->role       = $role->value;
        $this->permission = $permission->value;
    }

    /**
     * Property getter.
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getRole(): SystemRoleEnum
    {
        return SystemRoleEnum::from($this->role);
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getPermission(): TemplatePermissionEnum
    {
        return TemplatePermissionEnum::from($this->permission);
    }
}
