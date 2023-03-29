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

use App\Entity\Template;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @internal A dummy class for OpenAPI documentation
 */
abstract class TemplateExtended extends Template
{
    // Available actions.
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOCK   = 'lock';
    public const ACTION_UNLOCK = 'unlock';

    /**
     * List of actions currently available to the user.
     */
    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, properties: [
        new API\Property(property: self::ACTION_UPDATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the template can be updated.'),
        new API\Property(property: self::ACTION_DELETE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the template can be deleted.'),
        new API\Property(property: self::ACTION_LOCK, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the template can be locked.'),
        new API\Property(property: self::ACTION_UNLOCK, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the template can be unlocked.'),
    ])]
    abstract public function getActions();
}
