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

namespace App\Serializer\Normalizer;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizer for backed enums.
 */
class EnumDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): \BackedEnum
    {
        if (!is_subclass_of($type, \BackedEnum::class)) {
            throw new InvalidArgumentException('The data must belong to a backed enumeration.');
        }

        $value = null;

        if (\is_string($data)) {
            $value = $type::tryFrom($data);
        }

        if (null === $value) {
            throw new BadRequestHttpException(
                sprintf(
                    'The value should be one of the following - [%s].',
                    implode(', ', array_map(fn (\BackedEnum $case) => $case->value, $type::cases()))
                )
            );
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
