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

namespace App\Message\Fields;

/**
 * Sets new position for specified field.
 */
final class SetFieldPositionCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $field, private readonly int $position)
    {
    }

    /**
     * @return int Field ID
     */
    public function getField(): int
    {
        return $this->field;
    }

    /**
     * @return int Field position (one-based)
     */
    public function getPosition(): int
    {
        return $this->position;
    }
}
