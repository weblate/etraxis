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

use App\Entity\Group;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\GroupEntityNormalizer
 */
final class GroupEntityNormalizerTest extends TransactionalTestCase
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

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $expected = [
            'id'          => $group->getId(),
            'project'     => $this->normalizer->normalize($group->getProject(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $group->getName(),
            'description' => $group->getDescription(),
            'global'      => $group->isGlobal(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($group, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $expected = [
            'id'          => $group->getId(),
            'project'     => $this->normalizer->normalize($group->getProject(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $group->getName(),
            'description' => $group->getDescription(),
            'global'      => $group->isGlobal(),
            'actions'     => [
                'update' => true,
                'delete' => true,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($group, 'json', [
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

        $normalizer = new GroupEntityNormalizer($security);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertTrue($normalizer->supportsNormalization($group));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
