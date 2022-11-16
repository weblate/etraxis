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
 * Removes account from specified groups.
 */
final class RemoveGroupsCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $user,
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $groups
    ) {
    }

    /**
     * @return int User ID
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return array Group IDs
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
