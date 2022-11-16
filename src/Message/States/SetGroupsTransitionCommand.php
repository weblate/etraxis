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
 * Sets state transition for the specified groups.
 */
final class SetGroupsTransitionCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $fromState,
        private readonly int $toState,
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $groups
    ) {
    }

    /**
     * @return int State ID the transition goes from
     */
    public function getFromState(): int
    {
        return $this->fromState;
    }

    /**
     * @return int State ID the transition goes to
     */
    public function getToState(): int
    {
        return $this->toState;
    }

    /**
     * @return array Granted group IDs
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
