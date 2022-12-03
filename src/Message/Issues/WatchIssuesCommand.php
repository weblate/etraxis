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

namespace App\Message\Issues;

use App\Controller\ApiControllerInterface;
use App\MessageBus\Contracts\CommandInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Starts watching for specified issues.
 */
final class WatchIssuesCommand implements CommandInterface
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
        #[API\Property(type: ApiControllerInterface::TYPE_ARRAY, items: new API\Items(type: ApiControllerInterface::TYPE_INTEGER))]
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
