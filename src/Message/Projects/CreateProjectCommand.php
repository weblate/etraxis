<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Message\Projects;

use App\Entity\Project;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new project.
 */
final class CreateProjectCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: Project::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Assert\Length(max: Project::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description,
        #[Groups('api')]
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
