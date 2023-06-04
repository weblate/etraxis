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
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'FieldValue' entity normalizer.
 */
class FieldValueEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly FieldValueRepositoryInterface $repository)
    {
    }

    /**
     * @see NormalizerInterface::normalize
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        // Setting this to flag that the normalizer has been already called before.
        $context[self::class] = true;

        /** @var FieldValue $object */
        $result = $this->normalizer->normalize($object, $format, $context);

        if (null !== $object->getValue()) {
            $value = $this->repository->getFieldValue($object->getField()->getType(), $object->getValue());

            $result['value'] = is_object($value)
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
        return $data instanceof FieldValue && !($context[self::class] ?? false);
    }
}
