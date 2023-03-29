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

use App\Entity\Template;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\TemplateEntityNormalizer
 */
final class TemplateEntityNormalizerTest extends TransactionalTestCase
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

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $expected = [
            'id'          => $template->getId(),
            'project'     => $this->normalizer->normalize($template->getProject(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $template->getName(),
            'prefix'      => $template->getPrefix(),
            'description' => $template->getDescription(),
            'locked'      => $template->isLocked(),
            'criticalAge' => $template->getCriticalAge(),
            'frozenTime'  => $template->getFrozenTime(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($template, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $expected = [
            'id'          => $template->getId(),
            'project'     => $this->normalizer->normalize($template->getProject(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $template->getName(),
            'prefix'      => $template->getPrefix(),
            'description' => $template->getDescription(),
            'locked'      => $template->isLocked(),
            'criticalAge' => $template->getCriticalAge(),
            'frozenTime'  => $template->getFrozenTime(),
            'actions'     => [
                'update' => true,
                'delete' => false,
                'lock'   => true,
                'unlock' => false,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($template, 'json', [
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

        $normalizer = new TemplateEntityNormalizer($security);

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        self::assertTrue($normalizer->supportsNormalization($template));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
