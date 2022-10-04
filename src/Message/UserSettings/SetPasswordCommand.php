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

/**
 * Sets password for specified account.
 */
final class SetPasswordCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $user, private readonly string $password)
    {
    }

    /**
     * @return int User ID
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return string New password
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
