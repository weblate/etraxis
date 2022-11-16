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

namespace App\Message\States;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sets specified responsible groups for the state.
 */
final class SetResponsibleGroupsCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $state,
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $groups
    ) {
    }

    /**
     * @return int State ID
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return array Group IDs
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
