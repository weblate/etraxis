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

use App\Entity\Change;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\StringValueRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'Change' entity normalizer.
 */
class ChangeEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly StringValueRepositoryInterface $stringValueRepository,
        protected readonly FieldValueRepositoryInterface $fieldValueRepository
    ) {
    }

    /**
     * @see NormalizerInterface::normalize
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        // Setting this to flag that the normalizer has been already called before.
        $context[self::class] = true;

        /** @var Change $object */
        $result = $this->normalizer->normalize($object, $format, $context);

        if (null !== $object->getOldValue()) {
            $value = null === $object->getField()
                ? $this->stringValueRepository->find($object->getOldValue())?->getValue()
                : $this->fieldValueRepository->getFieldValue($object->getField()->getType(), $object->getOldValue());

            $result['oldValue'] = is_object($value)
                ? $this->normalizer->normalize($value, $format, $context)
                : $value;
        }

        if (null !== $object->getNewValue()) {
            $value = null === $object->getField()
                ? $this->stringValueRepository->find($object->getNewValue())?->getValue()
                : $this->fieldValueRepository->getFieldValue($object->getField()->getType(), $object->getNewValue());

            $result['newValue'] = is_object($value)
                ? $this->normalizer->normalize($value, $format, $context)
                : $value;
        }

        return $result;
    }

    /**
     * @see NormalizerInterface::supportsNormalization
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Change && !($context[self::class] ?? false);
    }
}
