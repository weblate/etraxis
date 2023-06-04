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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract field strategy with the default implementation.
 */
abstract class AbstractFieldStrategy implements FieldStrategyInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected Field $field)
    {
    }

    /**
     * @see FieldStrategyInterface::getContext
     */
    public function getContext(): Field
    {
        return $this->field;
    }

    /**
     * @see FieldStrategyInterface::getParameter
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        return null;
    }

    /**
     * @see FieldStrategyInterface::setParameter
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        return $this;
    }

    /**
     * @see FieldStrategyInterface::getParametersValidationConstraints
     */
    public function getParametersValidationConstraints(TranslatorInterface $translator): array
    {
        return [];
    }

    /**
     * @see FieldStrategyInterface::getValueValidationConstraints
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        $constraints = [];

        if ($this->field->isRequired()) {
            $constraints[] = new Assert\NotBlank();
        }

        return $constraints;
    }

    /**
     * Converts specified arbitrary value to a valid boolean value.
     */
    protected static function toBoolean(null|bool|int|string $value): ?bool
    {
        return null === $value ? null : (bool) $value;
    }

    /**
     * Converts specified arbitrary value to a valid integer value.
     */
    protected static function toInteger(null|bool|int|string $value, int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): ?int
    {
        if (null !== $value) {
            $value = (int) $value;

            if ($value < $minimum) {
                $value = $minimum;
            }

            if ($value > $maximum) {
                $value = $maximum;
            }
        }

        return $value;
    }

    /**
     * Converts specified arbitrary value to a valid string value.
     */
    protected static function toString(null|bool|int|string $value, int $maximum = PHP_INT_MAX): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = (string) $value;

        if (mb_strlen($value) > $maximum) {
            $value = mb_substr($value, 0, $maximum);
        }

        return $value;
    }
}
