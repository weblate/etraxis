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

namespace App\Serializer\Normalizer;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Denormalizer for backed enums.
 */
class EnumDenormalizer implements DenormalizerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly TranslatorInterface $translator)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): \BackedEnum
    {
        if (!is_subclass_of($type, \BackedEnum::class)) {
            throw new InvalidArgumentException($this->translator->trans('This value is not valid.', domain: 'validators'));
        }

        $value = null;

        if (\is_string($data)) {
            $value = $type::tryFrom($data);
        }

        if (null === $value) {
            throw new BadRequestHttpException($this->translator->trans('The value you selected is not a valid choice.', domain: 'validators'));
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_subclass_of($type, \BackedEnum::class);
    }
}
