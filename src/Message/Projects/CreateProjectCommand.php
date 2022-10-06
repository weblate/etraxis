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
 * Creates new project.
 */
final class CreateProjectCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $suspended
    ) {
    }

    /**
     * @return string Project name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string Description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool Status
     */
    public function isSuspended(): bool
    {
        return $this->suspended;
    }
}
