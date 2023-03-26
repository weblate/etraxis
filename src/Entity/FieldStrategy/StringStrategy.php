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
use App\Entity\StringValue;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * String field strategy.
 */
final class StringStrategy extends AbstractFieldStrategy
{
    // Constraints.
    public const MIN_LENGTH = 1;
    public const MAX_LENGTH = StringValue::MAX_VALUE;

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_INTEGER, minimum: self::MIN_LENGTH, maximum: self::MAX_LENGTH, description: 'Maximum allowed length.')]
    public function getLength(): int
    {
        return $this->getParameter(Field::LENGTH);
    }

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, maxLength: self::MAX_LENGTH, description: 'Default value.')]
    public function getDefault(): ?string
    {
        return $this->getParameter(Field::DEFAULT);
    }

    #[Groups('api')]
    #[SerializedName('pcre-check')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, maxLength: Field::MAX_PCRE, description: 'Perl-compatible regular expression to check value format.')]
    public function getPcreCheck(): ?string
    {
        return $this->getParameter(Field::PCRE_CHECK);
    }

    #[Groups('api')]
    #[SerializedName('pcre-search')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, maxLength: Field::MAX_PCRE, description: 'Perl-compatible regular expression to modify value before display it (search for).')]
    public function getPcreSearch(): ?string
    {
        return $this->getParameter(Field::PCRE_SEARCH);
    }

    #[Groups('api')]
    #[SerializedName('pcre-replace')]
    #[API\Property(type: OpenApiInterface::TYPE_STRING, nullable: true, maxLength: Field::MAX_PCRE, description: 'Perl-compatible regular expression to modify value before display it (replace with).')]
    public function getPcreReplace(): ?string
    {
        return $this->getParameter(Field::PCRE_REPLACE);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        return match ($parameter) {
            Field::LENGTH       => self::toInteger($this->field->getParameter(Field::LENGTH), self::MIN_LENGTH, self::MAX_LENGTH) ?? self::MAX_LENGTH,
            Field::DEFAULT      => $this->field->getParameter(Field::DEFAULT),
            Field::PCRE_CHECK   => $this->field->getParameter(Field::PCRE_CHECK),
            Field::PCRE_SEARCH  => $this->field->getParameter(Field::PCRE_SEARCH),
            Field::PCRE_REPLACE => $this->field->getParameter(Field::PCRE_REPLACE),
            default             => null,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        switch ($parameter) {
            case Field::DEFAULT:
                $this->field->setParameter($parameter, self::toString($value, self::MAX_LENGTH));

                break;

            case Field::LENGTH:
                $this->field->setParameter($parameter, self::toInteger($value, self::MIN_LENGTH, self::MAX_LENGTH));

                break;

            case Field::PCRE_CHECK:
            case Field::PCRE_SEARCH:
            case Field::PCRE_REPLACE:
                $this->field->setParameter($parameter, self::toString($value, Field::MAX_PCRE));

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
            'default' => [
                new Assert\Length([
                    'max'        => $this->getParameter(Field::LENGTH),
                    'maxMessage' => $translator->trans('field.error.default_value_length', [
                        '%maximum%' => $this->getParameter(Field::LENGTH),
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

        $constraints[] = new Assert\Length([
            'max' => $this->getParameter(Field::LENGTH),
        ]);

        $pcre = $this->getParameter(Field::PCRE_CHECK);

        if ($pcre) {
            $constraints[] = new Assert\Regex([
                'pattern' => sprintf('/^%s$/', $pcre),
            ]);
        }

        return $constraints;
    }
}
