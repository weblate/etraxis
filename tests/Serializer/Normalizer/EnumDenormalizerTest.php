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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\EnumDenormalizer
 */
final class EnumDenormalizerTest extends WebTestCase
{
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = self::getContainer()->get('translator');
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalize(): void
    {
        $denormalizer = new EnumDenormalizer($this->translator);

        self::assertSame(AccountProviderEnum::eTraxis, $denormalizer->denormalize('etraxis', AccountProviderEnum::class));
        self::assertSame(AccountProviderEnum::LDAP, $denormalizer->denormalize('ldap', AccountProviderEnum::class));
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This value is not valid.');

        $denormalizer = new EnumDenormalizer($this->translator);

        $denormalizer->denormalize(AccountProviderEnum::eTraxis, EnumDenormalizer::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeNotString(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value you selected is not a valid choice.');

        $denormalizer = new EnumDenormalizer($this->translator);

        $denormalizer->denormalize(123, AccountProviderEnum::class);
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeInvalidValue(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The value you selected is not a valid choice.');

        $denormalizer = new EnumDenormalizer($this->translator);

        $denormalizer->denormalize('acme', AccountProviderEnum::class);
    }

    /**
     * @covers ::supportsDenormalization
     */
    public function testSupportsDenormalization(): void
    {
        $denormalizer = new EnumDenormalizer($this->translator);

        self::assertTrue($denormalizer->supportsDenormalization('etraxis', AccountProviderEnum::class));
        self::assertFalse($denormalizer->supportsDenormalization('etraxis', 'string'));
    }
}
