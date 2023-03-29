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

use App\Entity\Field;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\FieldEntityNormalizer
 */
final class FieldEntityNormalizerTest extends TransactionalTestCase
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

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $expected = [
            'id'          => $field->getId(),
            'state'       => $this->normalizer->normalize($field->getState(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $field->getName(),
            'type'        => $field->getType()->value,
            'description' => $field->getDescription(),
            'position'    => $field->getPosition(),
            'required'    => $field->isRequired(),
            'parameters'  => $field->getAllParameters(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($field, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $expected = [
            'id'          => $field->getId(),
            'state'       => $this->normalizer->normalize($field->getState(), 'json', [AbstractNormalizer::GROUPS => 'api']),
            'name'        => $field->getName(),
            'type'        => $field->getType()->value,
            'description' => $field->getDescription(),
            'position'    => $field->getPosition(),
            'required'    => $field->isRequired(),
            'parameters'  => $field->getAllParameters(),
            'actions'     => [
                'update'  => true,
                'delete'  => true,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($field, 'json', [
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

        $normalizer = new FieldEntityNormalizer($security);

        /** @var Field $field */
        [/* skipping */ , $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        self::assertTrue($normalizer->supportsNormalization($field));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
