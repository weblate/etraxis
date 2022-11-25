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

namespace App\Message\Users;

use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Disables specified accounts.
 */
final class DisableUsersCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $users
    ) {
    }

    /**
     * @return array User IDs
     */
    public function getUsers(): array
    {
        return $this->users;
    }
}
