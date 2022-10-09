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
 * Updates specified field.
 */
final class UpdateFieldCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $required,
        private readonly ?array $parameters
    ) {
    }

    /**
     * @return int Field ID
     */
    public function getField(): int
    {
        return $this->field;
    }

    /**
     * @return string Field name
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
     * @return bool Whether the field is required
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return null|array Field parameters
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }
}
