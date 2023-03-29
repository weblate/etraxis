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

use App\Entity\State;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\StateEntityNormalizer
 */
final class StateEntityNormalizerTest extends TransactionalTestCase
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

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $expected = [
            'id'          => $state->getId(),
            'template'    => $this->normalizer->normalize($state->getTemplate(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $state->getName(),
            'type'        => $state->getType()->value,
            'responsible' => $state->getResponsible()->value,
        ];

        self::assertSame($expected, $this->normalizer->normalize($state, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $expected = [
            'id'          => $state->getId(),
            'template'    => $this->normalizer->normalize($state->getTemplate(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $state->getName(),
            'type'        => $state->getType()->value,
            'responsible' => $state->getResponsible()->value,
            'actions'     => [
                'update'      => true,
                'delete'      => false,
                'initial'     => true,
                'transitions' => true,
                'groups'      => true,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($state, 'json', [
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

        $normalizer = new StateEntityNormalizer($security);

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        self::assertTrue($normalizer->supportsNormalization($state));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
