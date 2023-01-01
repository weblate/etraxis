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

namespace App\Message\Fields;

use App\Entity\Enums\FieldPermissionEnum;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sets specified groups permission for the field.
 */
final class SetGroupsPermissionCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        private readonly FieldPermissionEnum $permission,
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $groups
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
     * @return array Granted group IDs
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
