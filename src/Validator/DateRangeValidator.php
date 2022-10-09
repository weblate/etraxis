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

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * A validator for the DateRange constraint.
 */
class DateRangeValidator extends ConstraintValidator implements ConstraintValidatorInterface
{
    /**
     * @see ConstraintValidatorInterface
     *
     * @param null|mixed $value
     * @param DateRange  $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null !== $value) {
            if (preg_match(DateRange::PCRE_PATTERN, $value)) {
                if (null !== $constraint->min && null !== $constraint->max) {
                    if ($value < $constraint->min || $value > $constraint->max) {
                        $this->context->addViolation($constraint->notInRangeMessage, [
                            '{{ min }}' => $constraint->min,
                            '{{ max }}' => $constraint->max,
                        ]);
                    }
                } elseif (null !== $constraint->min && null === $constraint->max && $value < $constraint->min) {
                    $this->context->addViolation($constraint->minMessage, ['{{ limit }}' => $constraint->min]);
                } elseif (null === $constraint->min && null !== $constraint->max && $value > $constraint->max) {
                    $this->context->addViolation($constraint->maxMessage, ['{{ limit }}' => $constraint->max]);
                }
            } else {
                $this->context->addViolation($constraint->invalidMessage);
            }
        }
    }
}
