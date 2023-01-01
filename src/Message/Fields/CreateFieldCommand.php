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

namespace App\Message\Fields;

use App\Controller\ApiControllerInterface;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldStrategy;
use App\MessageBus\Contracts\CommandInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
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
        #[Groups('api')]
        private readonly int $state,
        #[Assert\NotBlank]
        #[Assert\Length(max: Field::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Groups('api')]
        private readonly FieldTypeEnum $type,
        #[Assert\Length(max: Field::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description,
        #[Groups('api')]
        private readonly bool $required,
        #[Groups('api')]
        #[API\Property(type: ApiControllerInterface::TYPE_OBJECT, oneOf: [
            new API\Schema(ref: new Model(type: FieldStrategy\CheckboxStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\DateStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\DecimalStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\DurationStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\IssueStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\ListStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\NumberStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\StringStrategy::class)),
            new API\Schema(ref: new Model(type: FieldStrategy\TextStrategy::class)),
        ])]
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
