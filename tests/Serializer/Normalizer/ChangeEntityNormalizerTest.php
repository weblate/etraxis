<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Serializer\Normalizer;

use App\Entity\Change;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\StringValue;
use App\Entity\User;
use App\Repository\Contracts\ChangeRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\ChangeEntityNormalizer
 */
final class ChangeEntityNormalizerTest extends TransactionalTestCase
{
    private ChangeRepositoryInterface $repository;
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Change::class);
        $this->normalizer = self::getContainer()->get('serializer');
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeSubject(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $changes = $this->repository->findAllByIssue($issue, $user);
        $changes = array_filter($changes, fn (Change $change) => null === $change->getField());

        /** @var Change $change */
        $change = reset($changes);

        $expected = [
            'field'     => null,
            'oldValue'  => 'Task 1',
            'newValue'  => 'Development task 1',
            'user'      => [
                'id'       => $change->getUser()->getId(),
                'email'    => $change->getUser()->getEmail(),
                'fullname' => $change->getUser()->getFullname(),
            ],
            'createdAt' => $change->getCreatedAt(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($change, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeListItem(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $changes = $this->repository->findAllByIssue($issue, $user);
        $changes = array_filter($changes, fn (Change $change) => FieldTypeEnum::List === $change->getField()?->getType());

        /** @var Change $change */
        $change = reset($changes);

        $expected = [
            'field'     => [
                'id'          => $change->getField()->getId(),
                'name'        => 'Priority',
                'type'        => 'list',
                'description' => $change->getField()->getDescription(),
                'position'    => $change->getField()->getPosition(),
            ],
            'oldValue'  => [
                'value' => 3,
                'text'  => 'low',
            ],
            'newValue'  => [
                'value' => 2,
                'text'  => 'normal',
            ],
            'user'      => [
                'id'       => $change->getUser()->getId(),
                'email'    => $change->getUser()->getEmail(),
                'fullname' => $change->getUser()->getFullname(),
            ],
            'createdAt' => $change->getCreatedAt(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($change, 'json', [AbstractNormalizer::GROUPS => 'info']));
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

        $changes = $this->repository->findAllByIssue($issue, $user);
        $changes = array_filter($changes, fn (Change $change) => FieldTypeEnum::Text === $change->getField()?->getType());

        /** @var Change $change */
        $change = reset($changes);

        $expected = [
            'field'     => [
                'id'          => $change->getField()->getId(),
                'name'        => 'Description',
                'type'        => 'text',
                'description' => $change->getField()->getDescription(),
                'position'    => $change->getField()->getPosition(),
            ],
            'oldValue'  => 'Velit voluptatem rerum nulla quos.',
            'newValue'  => 'Velit voluptatem rerum nulla quos soluta excepturi omnis.',
            'user'      => [
                'id'       => $change->getUser()->getId(),
                'email'    => $change->getUser()->getEmail(),
                'fullname' => $change->getUser()->getFullname(),
            ],
            'createdAt' => $change->getCreatedAt(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($change, 'json', [AbstractNormalizer::GROUPS => 'info']));
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

        $changes = $this->repository->findAllByIssue($issue, $user);
        $changes = array_filter($changes, fn (Change $change) => FieldTypeEnum::List === $change->getField()?->getType());

        /** @var Change $change */
        $change = reset($changes);

        $normalizer = new ChangeEntityNormalizer(
            new ObjectNormalizer(),
            $this->doctrine->getRepository(StringValue::class),
            $this->doctrine->getRepository(FieldValue::class)
        );

        self::assertTrue($normalizer->supportsNormalization($change));
        self::assertFalse($normalizer->supportsNormalization($issue));
    }
}
