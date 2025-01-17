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

namespace App\Repository;

use App\Entity\TextValue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\TextValueRepository
 */
final class TextValueRepositoryTest extends TransactionalTestCase
{
    private Contracts\TextValueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(TextValue::class);
    }

    /**
     * @covers ::get
     */
    public function testGet(): void
    {
        $expected = 'Issue tracking system with customizable workflows.';

        $count = count($this->repository->findAll());

        /** @var TextValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var TextValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertSame($value1, $value);
        self::assertSame($expected, $value->getValue());
        self::assertCount($count + 1, $this->repository->findAll());

        // Second attempt.
        $value2 = $this->repository->get($expected);

        self::assertSame($value1, $value2);
        self::assertCount($count + 1, $this->repository->findAll());
    }
}
