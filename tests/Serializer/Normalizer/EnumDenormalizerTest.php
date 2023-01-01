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

use App\Entity\Enums\LocaleEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\EnumDenormalizer
 */
final class EnumDenormalizerTest extends TestCase
{
    /**
     * @covers ::denormalize
     */
    public function testDenormalize(): void
    {
        $denormalizer = new EnumDenormalizer();

        self::assertSame(LocaleEnum::English, $denormalizer->denormalize('en', LocaleEnum::class));
        self::assertSame(LocaleEnum::Russian, $denormalizer->denormalize('ru', LocaleEnum::class));
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data must belong to a backed enumeration.');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize(LocaleEnum::English, EnumDenormalizer::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotString(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value should be one of the following - [en, ru].');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize(123, LocaleEnum::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeInvalidValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value should be one of the following - [en, ru].');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize('xx', LocaleEnum::class);
    }

    /**
     * @covers ::supportsDenormalization
     */
    public function testSupportsDenormalization(): void
    {
        $denormalizer = new EnumDenormalizer();

        self::assertTrue($denormalizer->supportsDenormalization('en', LocaleEnum::class));
        self::assertFalse($denormalizer->supportsDenormalization('en', 'string'));
    }
}
