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

/**
 * Sets specified state as initial in its template.
 */
final class SetInitialStateCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $state)
    {
    }

    /**
     * @return int State ID
     */
    public function getState(): int
    {
        return $this->state;
    }
}
