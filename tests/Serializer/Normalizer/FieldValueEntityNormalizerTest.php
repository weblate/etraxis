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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\User;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\TransactionalTestCase;
use App\Utils\SecondsEnum;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\FieldValueEntityNormalizer
 */
final class FieldValueEntityNormalizerTest extends TransactionalTestCase
{
    private FieldValueRepositoryInterface $repository;
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(FieldValue::class);
        $this->normalizer = self::getContainer()->get('serializer');
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeCheckbox(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Checkbox === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'New feature',
                'type'        => 'checkbox',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => false,
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeDate(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Date === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        /** @var \App\Entity\Event $event */
        $event = $issue->getEvents()->first();

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Due date',
                'type'        => 'date',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => $event->getCreatedAt() + 5 * SecondsEnum::OneDay->value,
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeDecimal(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Decimal === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Test coverage',
                'type'        => 'decimal',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => '98.49',
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeDuration(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Duration === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Effort',
                'type'        => 'duration',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => '1:20',
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeIssue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [$duplicate] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Issue === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Task ID',
                'type'        => 'issue',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => $duplicate->getFullId(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeListItem(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::List === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Priority',
                'type'        => 'list',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => [
                'value' => 1,
                'text'  => 'high',
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeNumber(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Number === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Delta',
                'type'        => 'number',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => 5173,
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeString(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::String === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Commit ID',
                'type'        => 'string',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => '940059027173b8e8e1e3e874681f012f1f3bcf1d',
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeText(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Text === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Description',
                'type'        => 'text',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => 'Velit voluptatem rerum nulla quos soluta excepturi omnis.',
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeNull(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Decimal === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $expected = [
            'field' => [
                'id'          => $value->getField()->getId(),
                'name'        => 'Test coverage',
                'type'        => 'decimal',
                'description' => $value->getField()->getDescription(),
                'position'    => $value->getField()->getPosition(),
            ],
            'value' => null,
        ];

        self::assertSame($expected, $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::List === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        $normalizer = new FieldValueEntityNormalizer($this->repository);

        self::assertTrue($normalizer->supportsNormalization($value));
        self::assertFalse($normalizer->supportsNormalization($issue));
    }
}
