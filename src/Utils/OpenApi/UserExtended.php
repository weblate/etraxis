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

namespace App\Utils\OpenApi;

use App\Entity\User;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @internal A dummy class for OpenAPI documentation
 */
abstract class UserExtended extends User
{
    // Available actions.
    public const ACTION_UPDATE  = 'update';
    public const ACTION_DELETE  = 'delete';
    public const ACTION_DISABLE = 'disable';
    public const ACTION_ENABLE  = 'enable';

    /**
     * List of actions currently available to the user.
     */
    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, properties: [
        new API\Property(property: self::ACTION_UPDATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the user can be updated.'),
        new API\Property(property: self::ACTION_DELETE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the user can be deleted.'),
        new API\Property(property: self::ACTION_DISABLE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the user can be disabled.'),
        new API\Property(property: self::ACTION_ENABLE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the user can be enabled.'),
    ])]
    abstract public function getActions();
}
