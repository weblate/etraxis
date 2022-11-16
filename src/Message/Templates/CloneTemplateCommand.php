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

namespace App\Message\Templates;

use App\Entity\Template;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Clones sepcified template.
 */
final class CloneTemplateCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        private readonly int $project,
        #[Assert\Length(max: Template::MAX_NAME)]
        private readonly string $name,
        #[Assert\Length(max: Template::MAX_PREFIX)]
        private readonly string $prefix,
        #[Assert\Length(max: Template::MAX_DESCRIPTION)]
        private readonly ?string $description,
        #[Assert\Range(min: 1)]
        private readonly ?int $criticalAge,
        #[Assert\Range(min: 1)]
        private readonly ?int $frozenTime
    ) {
    }

    /**
     * @return int Original template ID
     */
    public function getTemplate(): int
    {
        return $this->template;
    }

    /**
     * @return int ID of the project to clone to
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
