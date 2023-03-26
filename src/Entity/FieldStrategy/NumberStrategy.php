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

namespace App\Entity\FieldStrategy;

use App\Entity\Field;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Number field strategy.
 */
final class NumberStrategy extends AbstractFieldStrategy
{
    // Constraints.
    public const MIN_VALUE = -1000000000;
    public const MAX_VALUE = 1000000000;

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_INTEGER, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Minimum allowed value.')]
    public function getMinimum(): int
    {
        return $this->getParameter(Field::MINIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_INTEGER, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Maximum allowed value.')]
    public function getMaximum(): int
    {
        return $this->getParameter(Field::MAXIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_INTEGER, nullable: true, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Default value.')]
    public function getDefault(): ?int
    {
        return $this->getParameter(Field::DEFAULT);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        return match ($parameter) {
            Field::DEFAULT => self::toInteger($this->field->getParameter($parameter), self::MIN_VALUE, self::MAX_VALUE),
            Field::MINIMUM => self::toInteger($this->field->getParameter($parameter), self::MIN_VALUE, self::MAX_VALUE) ?? self::MIN_VALUE,
            Field::MAXIMUM => self::toInteger($this->field->getParameter($parameter), self::MIN_VALUE, self::MAX_VALUE) ?? self::MAX_VALUE,
            default        => null,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        switch ($parameter) {
            case Field::DEFAULT:
                $this->field->setParameter($parameter, self::toInteger($value, self::MIN_VALUE, self::MAX_VALUE));

                break;

            case Field::MINIMUM:
                $this->field->setParameter($parameter, self::toInteger($value, self::MIN_VALUE, self::MAX_VALUE) ?? self::MIN_VALUE);

                break;

            case Field::MAXIMUM:
                $this->field->setParameter($parameter, self::toInteger($value, self::MIN_VALUE, self::MAX_VALUE) ?? self::MAX_VALUE);

                break;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParametersValidationConstraints(TranslatorInterface $translator): array
    {
        return [
            'maximum' => [
                new Assert\Range([
                    'min'        => $this->getParameter(Field::MINIMUM),
                    'minMessage' => $translator->trans('field.error.min_max_values'),
                ]),
            ],
            'default' => [
                new Assert\Range([
                    'min'               => $this->getParameter(Field::MINIMUM),
                    'max'               => $this->getParameter(Field::MAXIMUM),
                    'notInRangeMessage' => $translator->trans('field.error.default_value_range', [
                        '%minimum%' => $this->getParameter(Field::MINIMUM),
                        '%maximum%' => $this->getParameter(Field::MAXIMUM),
                    ]),
                ]),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        $constraints = parent::getValueValidationConstraints($translator, $context);

        $constraints[] = new Assert\Regex([
            'pattern' => '/^(\-|\+)?\d+$/',
        ]);

        $constraints[] = new Assert\Range([
            'min'               => $this->getParameter(Field::MINIMUM),
            'max'               => $this->getParameter(Field::MAXIMUM),
            'notInRangeMessage' => $translator->trans('field.error.value_range', [
                '%name%'    => $this->field->getName(),
                '%minimum%' => $this->getParameter(Field::MINIMUM),
                '%maximum%' => $this->getParameter(Field::MAXIMUM),
            ]),
        ]);

        return $constraints;
    }
}
