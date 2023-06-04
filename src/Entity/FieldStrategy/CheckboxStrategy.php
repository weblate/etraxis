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
 * Checkbox field strategy.
 */
final class CheckboxStrategy extends AbstractFieldStrategy
{
    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_BOOLEAN, description: 'Default value.')]
    public function getDefault(): bool
    {
        return $this->getParameter(Field::DEFAULT);
    }

    /**
     * @see FieldStrategyInterface::getParameter
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        if (Field::DEFAULT === $parameter) {
            return (bool) $this->field->getParameter($parameter);
        }

        return null;
    }

    /**
     * @see FieldStrategyInterface::setParameter
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        if (Field::DEFAULT === $parameter) {
            $this->field->setParameter($parameter, self::toBoolean($value));
        }

        return $this;
    }

    /**
     * @see FieldStrategyInterface::getValueValidationConstraints
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        return [
            new Assert\Choice([
                'choices' => [false, true],
                'message' => $translator->trans('field.error.value_boolean', domain: 'fields', parameters: [
                    '%name%' => $this->field->getName(),
                ]),
            ]),
        ];
    }
}
