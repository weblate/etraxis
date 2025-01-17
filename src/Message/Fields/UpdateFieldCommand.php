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

use App\Entity\Field;
use App\Entity\FieldStrategy;
use App\MessageBus\Contracts\CommandInterface;
use App\Utils\OpenApiInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified field.
 */
final class UpdateFieldCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $field,
        #[Assert\NotBlank]
        #[Assert\Length(max: Field::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Assert\Length(max: Field::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description,
        #[Groups('api')]
        private readonly bool $required,
        #[Groups('api')]
        #[API\Property(type: OpenApiInterface::TYPE_OBJECT, oneOf: [
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
