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

use App\Entity\Field;
use App\Entity\ListItem;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\ListItemRepository
 */
final class ListItemRepositoryTest extends TransactionalTestCase
{
    private Contracts\ListItemRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    /**
     * @covers ::findAllByField
     */
    public function testFindAllByField(): void
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $items = $this->repository->findAllByField($field);

        $expected = [
            'high',
            'normal',
            'low',
        ];

        $actual = array_map(fn (ListItem $item) => $item->getText(), $items);

        self::assertCount(3, $items);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueSuccess(): void
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame('normal', $item->getText());
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueUnknown(): void
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 4);

        self::assertNull($item);
    }

    /**
     * @covers ::findOneByValue
     */
    public function testFindOneByValueWrongField(): void
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertNull($item);
    }
}
