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

/**
 * Disables specified account.
 */
final class DisableUserCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $user)
    {
    }

    /**
     * @return int User ID
     */
    public function getUser(): int
    {
        return $this->user;
    }
}
