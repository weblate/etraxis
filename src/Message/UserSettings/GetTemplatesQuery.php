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

namespace App\Message\UserSettings;

use App\MessageBus\Contracts\QueryInterface;

/**
 * Returns list of templates which specified user can use to create new issue.
 */
final class GetTemplatesQuery implements QueryInterface
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
