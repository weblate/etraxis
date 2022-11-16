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

use App\Entity\Project;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified project.
 */
final class UpdateProjectCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $project,
        #[Assert\Length(max: Project::MAX_NAME)]
        private readonly string $name,
        #[Assert\Length(max: Project::MAX_DESCRIPTION)]
        private readonly ?string $description,
        private readonly bool $suspended
    ) {
    }

    /**
     * @return int Project ID
     */
    public function getProject(): int
    {
        return $this->project;
    }

    /**
     * @return string New name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string New description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool New status
     */
    public function isSuspended(): bool
    {
        return $this->suspended;
    }
}
