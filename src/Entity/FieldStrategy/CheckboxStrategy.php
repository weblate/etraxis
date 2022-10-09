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

namespace App\Entity\FieldStrategy;

use App\Entity\Field;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checkbox field strategy.
 */
final class CheckboxStrategy extends AbstractFieldStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        if (Field::DEFAULT === $parameter) {
            return (bool) $this->field->getParameter($parameter);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        if (Field::DEFAULT === $parameter) {
            $this->field->setParameter($parameter, self::toBoolean($value));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        return [
            new Assert\Choice([
                'choices' => [false, true],
                'strict'  => true,
                'message' => $translator->trans('field.error.value_boolean', [
                    '%name%' => $this->field->getName(),
                ]),
            ]),
        ];
    }
}
