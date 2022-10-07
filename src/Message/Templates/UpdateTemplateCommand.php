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

/**
 * Updates specified template.
 */
final class UpdateTemplateCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        private readonly string $name,
        private readonly string $prefix,
        private readonly ?string $description,
        private readonly ?int $criticalAge,
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
