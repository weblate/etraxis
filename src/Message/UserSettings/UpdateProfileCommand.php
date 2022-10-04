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
 * Updates profile info of the current user.
 */
final class UpdateProfileCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly string $email, private readonly string $fullname)
    {
    }

    /**
     * @return string New email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string New full name
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }
}
