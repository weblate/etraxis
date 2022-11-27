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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\ConstraintViolationsNormalizer
 */
final class ConstraintViolationsNormalizerTest extends TestCase
{
    /**
     * @covers ::normalize
     */
    public function testNormalize(): void
    {
        $object = new class() {
            /**
             * @Range(min="1", max="100")
             */
            public int $property = 0;
        };

        $normalizer = new ConstraintViolationsNormalizer();

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation(
            'This value should be "1" or more.',
            'This value should be {{ limit }} or more.',
            [
                '{{ value }}' => '"0"',
                '{{ limit }}' => '"1"',
            ],
            $object,
            'property',
            '0'
        ));

        $expected = [
            [
                'property' => 'property',
                'value'    => '0',
                'message'  => 'This value should be "1" or more.',
            ],
        ];

        self::assertSame($expected, $normalizer->normalize($violations));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization(): void
    {
        $normalizer = new ConstraintViolationsNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new ConstraintViolationList()));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
