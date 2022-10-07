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

namespace App\Message\Templates;

use App\Entity\Enums\TemplatePermissionEnum;

/**
 * Sets specified roles permission for the template.
 */
final class SetRolesPermissionCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        private readonly TemplatePermissionEnum $permission,
        private readonly array $roles
    ) {
    }

    /**
     * @return int Template ID
     */
    public function getTemplate(): int
    {
        return $this->template;
    }

    /**
     * @return TemplatePermissionEnum Template permission
     */
    public function getPermission(): TemplatePermissionEnum
    {
        return $this->permission;
    }

    /**
     * @return \App\Entity\Enums\SystemRoleEnum[] Granted system roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
