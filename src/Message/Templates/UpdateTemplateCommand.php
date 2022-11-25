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
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified template.
 */
final class UpdateTemplateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        #[Assert\NotBlank]
        #[Assert\Length(max: Template::MAX_NAME)]
        private readonly string $name,
        #[Assert\NotBlank]
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
     * @return int Template ID
     */
    public function getTemplate(): int
    {
        return $this->template;
    }

    /**
     * @return string New name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string New prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return null|string New description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return null|int New critical age
     */
    public function getCriticalAge(): ?int
    {
        return $this->criticalAge;
    }

    /**
     * @return null|int New frozen time
     */
    public function getFrozenTime(): ?int
    {
        return $this->frozenTime;
    }
}
