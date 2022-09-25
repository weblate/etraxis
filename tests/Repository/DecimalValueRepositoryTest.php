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

namespace App\Repository;

use App\Entity\DecimalValue;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\DecimalValueRepository
 */
final class DecimalValueRepositoryTest extends TransactionalTestCase
{
    private ObjectRepository|Contracts\DecimalValueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(DecimalValue::class);
    }

    /**
     * @covers ::get
     */
    public function testGet(): void
    {
        $expected = '3.14159292';

        $count = count($this->repository->findAll());

        /** @var DecimalValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var DecimalValue $value */
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
