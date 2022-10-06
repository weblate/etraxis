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

namespace App\Message\Groups;

/**
 * Removes specified users to the group.
 */
final class RemoveMembersCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $group, private readonly array $users)
    {
    }

    /**
     * @return int Group ID
     */
    public function getGroup(): int
    {
        return $this->group;
    }

    /**
     * @return array User IDs
     */
    public function getUsers(): array
    {
        return $this->users;
    }
}
