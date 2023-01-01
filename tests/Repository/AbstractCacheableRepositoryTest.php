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

use App\Entity\DecimalValue;
use App\ReflectionTrait;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\AbstractCacheableRepository
 */
final class AbstractCacheableRepositoryTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private Contracts\CacheableRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(DecimalValue::class);
    }

    /**
     * @covers ::find
     */
    public function testFind(): void
    {
        /** @var DecimalValue $value1 */
        $value1 = $this->repository->findOneBy(['value' => '98.49']);

        /** @var DecimalValue $value2 */
        $value2 = $this->repository->findOneBy(['value' => '99.05']);

        self::assertCount(0, $this->getProperty($this->repository, 'cache'));

        self::assertSame($value1->getId(), $this->repository->find($value1->getId())->getId());
        self::assertCount(1, $this->getProperty($this->repository, 'cache'));

        self::assertNull($this->repository->find(null));
        self::assertCount(1, $this->getProperty($this->repository, 'cache'));

        self::assertNull($this->repository->find(self::UNKNOWN_ENTITY_ID));
        self::assertCount(1, $this->getProperty($this->repository, 'cache'));

        self::assertSame($value1->getId(), $this->repository->find($value1->getId())->getId());
        self::assertCount(1, $this->getProperty($this->repository, 'cache'));

        self::assertSame($value2->getId(), $this->repository->find($value2->getId())->getId());
        self::assertCount(2, $this->getProperty($this->repository, 'cache'));
    }

    /**
     * @covers ::warmup
     */
    public function testWarmup(): void
    {
        /** @var DecimalValue $value */
        $value = $this->repository->findOneBy(['value' => '98.49']);

        self::assertCount(0, $this->getProperty($this->repository, 'cache'));

        self::assertSame(1, $this->repository->warmup([$value->getId(), self::UNKNOWN_ENTITY_ID]));
        self::assertCount(1, $this->getProperty($this->repository, 'cache'));

        self::assertSame(0, $this->repository->warmup([self::UNKNOWN_ENTITY_ID]));
        self::assertCount(0, $this->getProperty($this->repository, 'cache'));
    }
}
