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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new field.
 */
final class CreateFieldCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $state,
        #[Assert\NotBlank]
        #[Assert\Length(max: Field::MAX_NAME)]
        private readonly string $name,
        private readonly FieldTypeEnum $type,
        #[Assert\Length(max: Field::MAX_DESCRIPTION)]
        private readonly ?string $description,
        private readonly bool $required,
        private readonly ?array $parameters
    ) {
    }

    /**
     * @return int ID of the field's state
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return string Field name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return FieldTypeEnum Type of the field
     */
    public function getType(): FieldTypeEnum
    {
        return $this->type;
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
