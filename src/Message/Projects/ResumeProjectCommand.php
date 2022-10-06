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

namespace App\Message\Projects;

/**
 * Resumes specified project.
 */
final class ResumeProjectCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $project)
    {
    }

    /**
     * @return int Project ID
     */
    public function getProject(): int
    {
        return $this->project;
    }
}
