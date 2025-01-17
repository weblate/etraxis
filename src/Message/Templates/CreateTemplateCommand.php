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

namespace App\Message\Templates;

use App\Entity\Template;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new template.
 */
final class CreateTemplateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Groups('api')]
        private readonly int $project,
        #[Assert\NotBlank]
        #[Assert\Length(max: Template::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Assert\NotBlank]
        #[Assert\Length(max: Template::MAX_PREFIX)]
        #[Groups('api')]
        private readonly string $prefix,
        #[Assert\Length(max: Template::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description,
        #[Assert\Range(min: 1)]
        #[Groups('api')]
        private readonly ?int $criticalAge,
        #[Assert\Range(min: 1)]
        #[Groups('api')]
        private readonly ?int $frozenTime
    ) {
    }

    /**
     * @return int ID of the template's project
     */
    public function getProject(): int
    {
        return $this->project;
    }

    /**
     * @return string Template name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string Template prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return null|string Description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return null|int Critical age
     */
    public function getCriticalAge(): ?int
    {
        return $this->criticalAge;
    }

    /**
     * @return null|int Frozen time
     */
    public function getFrozenTime(): ?int
    {
        return $this->frozenTime;
    }
}
