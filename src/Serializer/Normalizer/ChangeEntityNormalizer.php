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

use App\Entity\Change;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\StringValueRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'Change' entity normalizer.
 */
class ChangeEntityNormalizer implements NormalizerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly NormalizerInterface $normalizer,
        protected readonly StringValueRepositoryInterface $stringValueRepository,
        protected readonly FieldValueRepositoryInterface $fieldValueRepository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var Change $object */
        $json = $this->normalizer->normalize($object, $format, $context);

        if (null !== $object->getOldValue()) {
            $value = null === $object->getField()
                ? $this->stringValueRepository->find($object->getOldValue())?->getValue()
                : $this->fieldValueRepository->getFieldValue($object->getField()->getType(), $object->getOldValue());

            $json['oldValue'] = is_object($value)
                ? $this->normalizer->normalize($value, $format, $context)
                : $value;
        }

        if (null !== $object->getNewValue()) {
            $value = null === $object->getField()
                ? $this->stringValueRepository->find($object->getNewValue())?->getValue()
                : $this->fieldValueRepository->getFieldValue($object->getField()->getType(), $object->getNewValue());

            $json['newValue'] = is_object($value)
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
        return $data instanceof Change;
    }
}
