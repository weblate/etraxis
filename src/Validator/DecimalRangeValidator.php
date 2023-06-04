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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * A validator for the DecimalRange constraint.
 */
class DecimalRangeValidator extends ConstraintValidator implements ConstraintValidatorInterface
{
    protected const PRECISION = 0x7FFFFFFF;

    /**
     * @see ConstraintValidatorInterface::validate
     *
     * @param null|mixed   $value
     * @param DecimalRange $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null !== $value) {
            if (preg_match(DecimalRange::PCRE_PATTERN, $value)) {
                if (null !== $constraint->min && null !== $constraint->max) {
                    if (bccomp($value, $constraint->min, self::PRECISION) < 0 || bccomp($value, $constraint->max, self::PRECISION) > 0) {
                        $this->context->addViolation($constraint->notInRangeMessage, [
                            '{{ min }}' => $constraint->min,
                            '{{ max }}' => $constraint->max,
                        ]);
                    }
                } elseif (null !== $constraint->min && null === $constraint->max && bccomp($value, $constraint->min, self::PRECISION) < 0) {
                    $this->context->addViolation($constraint->minMessage, ['{{ limit }}' => $constraint->min]);
                } elseif (null === $constraint->min && null !== $constraint->max && bccomp($value, $constraint->max, self::PRECISION) > 0) {
                    $this->context->addViolation($constraint->maxMessage, ['{{ limit }}' => $constraint->max]);
                }
            } else {
                $this->context->addViolation($constraint->invalidMessage);
            }
        }
    }
}
