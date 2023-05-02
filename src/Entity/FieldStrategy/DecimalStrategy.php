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

use App\Entity\DecimalValue;
use App\Entity\Field;
use App\Utils\OpenApiInterface;
use App\Validator\DecimalRange;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Decimal field strategy.
 */
final class DecimalStrategy extends AbstractFieldStrategy
{
    // Constraints.
    public const MIN_VALUE = '-9999999999.9999999999';
    public const MAX_VALUE = '9999999999.9999999999';

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Minimum allowed value.')]
    public function getMinimum(): string
    {
        return $this->getParameter(Field::MINIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Maximum allowed value.')]
    public function getMaximum(): string
    {
        return $this->getParameter(Field::MAXIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, minimum: self::MIN_VALUE, maximum: self::MAX_VALUE, description: 'Default value.')]
    public function getDefault(): ?string
    {
        return $this->getParameter(Field::DEFAULT);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        return match ($parameter) {
            Field::DEFAULT => self::toDecimal($this->field->getParameter($parameter)),
            Field::MINIMUM => self::toDecimal($this->field->getParameter($parameter)) ?? self::MIN_VALUE,
            Field::MAXIMUM => self::toDecimal($this->field->getParameter($parameter)) ?? self::MAX_VALUE,
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
                $this->field->setParameter($parameter, self::toDecimal($value));

                break;

            case Field::MINIMUM:
                $this->field->setParameter($parameter, self::toDecimal($value) ?? self::MIN_VALUE);

                break;

            case Field::MAXIMUM:
                $this->field->setParameter($parameter, self::toDecimal($value) ?? self::MAX_VALUE);

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
                new DecimalRange([
                    'min'        => $this->getParameter(Field::MINIMUM),
                    'minMessage' => $translator->trans('field.error.min_max_values', domain: 'fields'),
                ]),
            ],
            'default' => [
                new DecimalRange([
                    'min'               => $this->getParameter(Field::MINIMUM),
                    'max'               => $this->getParameter(Field::MAXIMUM),
                    'notInRangeMessage' => $translator->trans('field.error.default_value_range', domain: 'fields', parameters: [
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
            'pattern' => '/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/',
        ]);

        $constraints[] = new DecimalRange([
            'min'               => $this->getParameter(Field::MINIMUM),
            'max'               => $this->getParameter(Field::MAXIMUM),
            'notInRangeMessage' => $translator->trans('field.error.value_range', domain: 'fields', parameters: [
                '%name%'    => $this->field->getName(),
                '%minimum%' => $this->getParameter(Field::MINIMUM),
                '%maximum%' => $this->getParameter(Field::MAXIMUM),
            ]),
        ]);

        return $constraints;
    }

    /**
     * Converts specified arbitrary value to a valid decimal value.
     */
    private function toDecimal(null|bool|int|string $value): ?string
    {
        if (null !== $value) {
            $value = (string) $value;

            if (bccomp($value, self::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                $value = self::MIN_VALUE;
            }

            if (bccomp($value, self::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                $value = self::MAX_VALUE;
            }
        }

        return $value;
    }
}
