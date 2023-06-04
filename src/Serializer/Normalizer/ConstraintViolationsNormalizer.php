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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Normalizer for a list of constraint violations.
 */
class ConstraintViolationsNormalizer implements NormalizerInterface
{
    /**
     * @see NormalizerInterface::normalize
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_map(fn (ConstraintViolationInterface $violation) => [
            'property' => $violation->getPropertyPath(),
            'value'    => $violation->getInvalidValue(),
            'message'  => $violation->getMessage(),
        ], $object->getIterator()->getArrayCopy());
    }

    /**
     * @see NormalizerInterface::supportsNormalization
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof ConstraintViolationListInterface;
    }
}
