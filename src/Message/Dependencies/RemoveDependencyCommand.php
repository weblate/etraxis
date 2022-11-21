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

namespace App\Message\Dependencies;

/**
 * Removes existing dependency from specified issue.
 */
final class RemoveDependencyCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $issue, private readonly int $dependency)
    {
    }

    /**
     * @return int Issue ID
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return int Dependency ID
     */
    public function getDependency(): int
    {
        return $this->dependency;
    }
}
