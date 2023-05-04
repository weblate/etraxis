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

use App\Entity\Enums\AccountProviderEnum;
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

        self::assertSame(AccountProviderEnum::eTraxis, $denormalizer->denormalize('etraxis', AccountProviderEnum::class));
        self::assertSame(AccountProviderEnum::LDAP, $denormalizer->denormalize('ldap', AccountProviderEnum::class));
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data must belong to a backed enumeration.');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize(AccountProviderEnum::eTraxis, EnumDenormalizer::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotString(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value should be one of the following - [etraxis, ldap].');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize(123, AccountProviderEnum::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeInvalidValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value should be one of the following - [etraxis, ldap].');

        $denormalizer = new EnumDenormalizer();

        $denormalizer->denormalize('acme', AccountProviderEnum::class);
    }

    /**
     * @covers ::supportsDenormalization
     */
    public function testSupportsDenormalization(): void
    {
        $denormalizer = new EnumDenormalizer();

        self::assertTrue($denormalizer->supportsDenormalization('etraxis', AccountProviderEnum::class));
        self::assertFalse($denormalizer->supportsDenormalization('etraxis', 'string'));
    }
}
