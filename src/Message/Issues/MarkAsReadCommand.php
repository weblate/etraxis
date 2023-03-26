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

namespace App\Message\Issues;

use App\MessageBus\Contracts\CommandInterface;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Marks specified issues as read.
 */
final class MarkAsReadCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        #[Groups('api')]
        #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(type: OpenApiInterface::TYPE_INTEGER))]
        private readonly array $issues
    ) {
    }

    /**
     * @return array Issue IDs
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
