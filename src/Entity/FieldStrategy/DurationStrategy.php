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
use App\Validator\DurationRange;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Duration field strategy.
 */
final class DurationStrategy extends AbstractFieldStrategy
{
    // Constraints.
    public const MIN_VALUE = 0;         // 0:00
    public const MAX_VALUE = 59999999;  // 999999:59

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, description: 'Minimum allowed value (0:00 - 999999:59).')]
    public function getMinimum(): string
    {
        return $this->getParameter(Field::MINIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, description: 'Maximum allowed value (0:00 - 999999:59).')]
    public function getMaximum(): string
    {
        return $this->getParameter(Field::MAXIMUM);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, description: 'Default value (0:00 - 999999:59).')]
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
            Field::DEFAULT => self::int2hhmm(self::toInteger(self::hhmm2int($this->field->getParameter($parameter)), self::MIN_VALUE, self::MAX_VALUE)),
            Field::MINIMUM => self::int2hhmm(self::toInteger(self::hhmm2int($this->field->getParameter($parameter)), self::MIN_VALUE, self::MAX_VALUE) ?? self::MIN_VALUE),
            Field::MAXIMUM => self::int2hhmm(self::toInteger(self::hhmm2int($this->field->getParameter($parameter)), self::MIN_VALUE, self::MAX_VALUE) ?? self::MAX_VALUE),
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
                $this->field->setParameter($parameter, self::int2hhmm(self::toInteger(self::hhmm2int($value), self::MIN_VALUE, self::MAX_VALUE)));

                break;

            case Field::MINIMUM:
                $this->field->setParameter($parameter, self::int2hhmm(self::toInteger(self::hhmm2int($value), self::MIN_VALUE, self::MAX_VALUE) ?? self::MIN_VALUE));

                break;

            case Field::MAXIMUM:
                $this->field->setParameter($parameter, self::int2hhmm(self::toInteger(self::hhmm2int($value), self::MIN_VALUE, self::MAX_VALUE) ?? self::MAX_VALUE));

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
                new DurationRange([
                    'min'        => $this->getParameter(Field::MINIMUM),
                    'minMessage' => $translator->trans('field.error.min_max_values'),
                ]),
            ],
            'default' => [
                new DurationRange([
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
            'pattern' => '/^\d{1,6}:[0-5]\d$/',
        ]);

        $constraints[] = new DurationRange([
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

    /**
     * Converts specified number of minutes to its string representation in format "hh:mm" (e.g. "2:07" for 127).
     */
    public static function int2hhmm(?int $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = self::toInteger($value, self::MIN_VALUE, self::MAX_VALUE);

        return sprintf('%s:%02s', intdiv($value, SecondsEnum::OneMinute->value), $value % SecondsEnum::OneMinute->value);
    }

    /**
     * Converts specified string representation of amount of minutes to an integer number (e.g. 127 for "2:07").
     */
    public static function hhmm2int(?string $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!preg_match('/^\d{1,6}:[0-5]\d$/', $value)) {
            return null;
        }

        [$hh, $mm] = explode(':', $value);

        return self::toInteger($hh, 0, 999999) * SecondsEnum::OneMinute->value + self::toInteger($mm, 0, 59);
    }
}
