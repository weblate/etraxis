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
use App\Utils\SecondsEnum;
use App\Validator\DateRange;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Date field strategy.
 */
final class DateStrategy extends AbstractFieldStrategy
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
     * @see FieldStrategyInterface::getParameter
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
     * @see FieldStrategyInterface::setParameter
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
     * @see FieldStrategyInterface::getParametersValidationConstraints
     */
    public function getParametersValidationConstraints(TranslatorInterface $translator): array
    {
        return [
            'maximum' => [
                new Assert\Range([
                    'min'        => $this->getParameter(Field::MINIMUM),
                    'minMessage' => $translator->trans('field.error.min_max_values', domain: 'fields'),
                ]),
            ],
            'default' => [
                new Assert\Range([
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
     * @see FieldStrategyInterface::getValueValidationConstraints
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        $constraints = parent::getValueValidationConstraints($translator, $context);

        $constraints[] = new Assert\Regex([
            'pattern' => DateRange::PCRE_PATTERN,
        ]);

        $timestamp = (int) ($context[$this->getContext()->getId()] ?? time());
        $formatter = new \IntlDateFormatter($translator->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

        $constraints[] = new DateRange([
            'min'               => date('Y-m-d', $timestamp + SecondsEnum::OneDay->value * $this->getParameter(Field::MINIMUM)),
            'max'               => date('Y-m-d', $timestamp + SecondsEnum::OneDay->value * $this->getParameter(Field::MAXIMUM)),
            'notInRangeMessage' => $translator->trans('field.error.value_range', domain: 'fields', parameters: [
                '%name%'    => $this->field->getName(),
                '%minimum%' => $formatter->format($timestamp + SecondsEnum::OneDay->value * $this->getParameter(Field::MINIMUM)),
                '%maximum%' => $formatter->format($timestamp + SecondsEnum::OneDay->value * $this->getParameter(Field::MAXIMUM)),
            ]),
        ]);

        return $constraints;
    }
}
