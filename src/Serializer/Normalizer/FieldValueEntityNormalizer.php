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

use App\Entity\FieldValue;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'FieldValue' entity normalizer.
 */
class FieldValueEntityNormalizer implements NormalizerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly NormalizerInterface $normalizer,
        protected readonly FieldValueRepositoryInterface $repository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var FieldValue $object */
        $json = $this->normalizer->normalize($object, $format, $context);

        if (null !== $object->getValue()) {
            $value = $this->repository->getFieldValue($object->getField()->getType(), $object->getValue());

            $json['value'] = is_object($value)
                ? $this->normalizer->normalize($value, $format, $context)
                : $value;
        }

        return $json;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof FieldValue;
    }
}
