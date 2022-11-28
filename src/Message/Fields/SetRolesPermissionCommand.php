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

namespace App\Message\Fields;

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sets specified roles permission for the field.
 */
final class SetRolesPermissionCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        private readonly FieldPermissionEnum $permission,
        #[Assert\Choice(callback: [SystemRoleEnum::class, 'cases'], multiple: true)]
        private readonly array $roles
    ) {
    }

    /**
     * @return int Field ID
     */
    public function getField(): int
    {
        return $this->field;
    }

    /**
     * @return FieldPermissionEnum Field permission
     */
    public function getPermission(): FieldPermissionEnum
    {
        return $this->permission;
    }

    /**
     * @return array Granted system roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
