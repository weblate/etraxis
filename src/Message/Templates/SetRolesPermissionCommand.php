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

namespace App\Message\Templates;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sets specified roles permission for the template.
 */
final class SetRolesPermissionCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        private readonly TemplatePermissionEnum $permission,
        #[Assert\Choice(callback: [SystemRoleEnum::class, 'cases'], multiple: true)]
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
     * @return SystemRoleEnum[] Granted system roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
