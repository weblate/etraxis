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

use App\Entity\Project;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\ProjectEntityNormalizer
 */
final class ProjectEntityNormalizerTest extends TransactionalTestCase
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

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $expected = [
            'id'          => $project->getId(),
            'name'        => $project->getName(),
            'description' => $project->getDescription(),
            'createdAt'   => $project->getCreatedAt(),
            'suspended'   => $project->isSuspended(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($project, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $expected = [
            'id'          => $project->getId(),
            'name'        => $project->getName(),
            'description' => $project->getDescription(),
            'createdAt'   => $project->getCreatedAt(),
            'suspended'   => $project->isSuspended(),
            'actions'     => [
                'update'  => true,
                'delete'  => false,
                'suspend' => false,
                'resume'  => true,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($project, 'json', [
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

        $normalizer = new ProjectEntityNormalizer($security);

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        self::assertTrue($normalizer->supportsNormalization($project));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
