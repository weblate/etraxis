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

use App\Entity\User;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\UserEntityNormalizer
 */
final class UserEntityNormalizerTest extends TransactionalTestCase
{
    use LoginTrait;

    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = self::getContainer()->get('serializer');
    }

    /**
     * @covers ::normalize
     */
    public function testNormalize(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('artem@example.com');

        $expected = [
            'id'              => $user->getId(),
            'email'           => $user->getEmail(),
            'fullname'        => $user->getFullname(),
            'description'     => $user->getDescription(),
            'admin'           => $user->isAdmin(),
            'disabled'        => $user->isDisabled(),
            'accountProvider' => $user->getAccountProvider()->value,
            'locale'          => $user->getLocale()->value,
            'timezone'        => $user->getTimezone(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($user, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $expected = [
            'id'              => $user->getId(),
            'email'           => $user->getEmail(),
            'fullname'        => $user->getFullname(),
            'description'     => $user->getDescription(),
            'admin'           => $user->isAdmin(),
            'disabled'        => $user->isDisabled(),
            'accountProvider' => $user->getAccountProvider()->value,
            'locale'          => $user->getLocale()->value,
            'timezone'        => $user->getTimezone(),
            'actions'         => [
                'update'  => true,
                'delete'  => false,
                'disable' => true,
                'enable'  => false,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($user, 'json', [
            AbstractNormalizer::GROUPS => 'api',
            OpenApiInterface::ACTIONS  => true,
        ]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization(): void
    {
        $security = self::getContainer()->get('security.authorization_checker');

        $normalizer = new UserEntityNormalizer($security);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        self::assertTrue($normalizer->supportsNormalization($user));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
