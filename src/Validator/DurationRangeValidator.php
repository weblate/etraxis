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

namespace App\Validator;

use App\Utils\SecondsEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * A validator for the DurationRange constraint.
 */
class DurationRangeValidator extends ConstraintValidator implements ConstraintValidatorInterface
{
    /**
     * @see ConstraintValidatorInterface::validate
     *
     * @param null|mixed    $value
     * @param DurationRange $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null !== $value) {
            if (preg_match(DurationRange::PCRE_PATTERN, $value)) {
                $duration = $this->str2int($value);

                if (null !== $constraint->min && null !== $constraint->max) {
                    if ($duration < $this->str2int($constraint->min) || $duration > $this->str2int($constraint->max)) {
                        $this->context->addViolation($constraint->notInRangeMessage, [
                            '{{ min }}' => $constraint->min,
                            '{{ max }}' => $constraint->max,
                        ]);
                    }
                } elseif (null !== $constraint->min && null === $constraint->max && $duration < $this->str2int($constraint->min)) {
                    $this->context->addViolation($constraint->minMessage, ['{{ limit }}' => $constraint->min]);
                } elseif (null === $constraint->min && null !== $constraint->max && $duration > $this->str2int($constraint->max)) {
                    $this->context->addViolation($constraint->maxMessage, ['{{ limit }}' => $constraint->max]);
                }
            } else {
                $this->context->addViolation($constraint->invalidMessage);
            }
        }
    }

    /**
     * Converts string with duration to its integer value.
     */
    protected function str2int(string $value): int
    {
        [$hh, $mm] = explode(':', $value);

        return (int) $hh * SecondsEnum::OneMinute->value + (int) $mm;
    }
}
