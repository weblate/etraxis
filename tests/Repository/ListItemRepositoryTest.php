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

use App\Entity\Field;
use App\Entity\ListItem;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\ListItemRepository
 */
final class ListItemRepositoryTest extends TransactionalTestCase
{
    private ObjectRepository|Contracts\ListItemRepositoryInterface $repository;

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

        $actual = array_map(fn (ListItem $item) => $item->getItemText(), $items);

        self::assertCount(3, $items);
        self::assertSame($expected, $actual);
    }
}
