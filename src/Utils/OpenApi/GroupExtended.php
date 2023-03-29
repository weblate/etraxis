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

use App\Entity\Group;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @internal A dummy class for OpenAPI documentation
 */
abstract class GroupExtended extends Group
{
    // Available actions.
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    /**
     * List of actions currently available to the user.
     */
    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, properties: [
        new API\Property(property: self::ACTION_UPDATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the group can be updated.'),
        new API\Property(property: self::ACTION_DELETE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the group can be deleted.'),
    ])]
    abstract public function getActions();
}
