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

use App\Entity\State;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @internal A dummy class for OpenAPI documentation
 */
abstract class StateExtended extends State
{
    // Available actions.
    public const ACTION_UPDATE      = 'update';
    public const ACTION_DELETE      = 'delete';
    public const ACTION_INITIAL     = 'initial';
    public const ACTION_TRANSITIONS = 'transitions';
    public const ACTION_GROUPS      = 'groups';

    /**
     * List of actions currently available to the user.
     */
    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, properties: [
        new API\Property(property: self::ACTION_UPDATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the state can be updated.'),
        new API\Property(property: self::ACTION_DELETE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the state can be deleted.'),
        new API\Property(property: self::ACTION_INITIAL, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the state can be set as initial.'),
        new API\Property(property: self::ACTION_TRANSITIONS, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the transitions can be managed.'),
        new API\Property(property: self::ACTION_GROUPS, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the responsible groups can be managed.'),
    ])]
    abstract public function getActions();
}
