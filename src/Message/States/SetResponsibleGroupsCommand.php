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

namespace App\Message\States;

use App\MessageBus\Contracts\CommandInterface;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sets specified responsible groups for the state.
 */
final class SetResponsibleGroupsCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $state,
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        #[Groups('api')]
        #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(type: OpenApiInterface::TYPE_INTEGER))]
        private readonly array $groups
    ) {
    }

    /**
     * @return int State ID
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return array Group IDs
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
