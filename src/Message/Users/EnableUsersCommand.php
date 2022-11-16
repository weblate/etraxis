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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Enables specified accounts.
 */
final class EnableUsersCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\Count(min: 1)]
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
