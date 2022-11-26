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

use App\Message\AbstractCollectionQuery;
use App\Message\Users\GetUsersQuery;
use App\Message\UserSettings\UpdateProfileCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\CollectionQueryDenormalizer
 */
final class CollectionQueryDenormalizerTest extends TestCase
{
    /**
     * @covers ::denormalize
     */
    public function testDenormalize(): void
    {
        $denormalizer = new CollectionQueryDenormalizer();

        $filters = [
            GetUsersQuery::USER_EMAIL       => '@example.com',
            GetUsersQuery::USER_IS_DISABLED => true,
        ];

        $order = [
            GetUsersQuery::USER_PROVIDER => AbstractCollectionQuery::SORT_DESC,
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_DESC,
        ];

        $request = new Request(query: [
            'offset'  => 50,
            'limit'   => 25,
            'search'  => 'test',
            'filters' => json_encode($filters),
            'order'   => json_encode($order),
        ]);

        $query = $denormalizer->denormalize($request, GetUsersQuery::class);

        self::assertSame(50, $query->getOffset());
        self::assertSame(25, $query->getLimit());
        self::assertSame('test', $query->getSearch());
        self::assertSame($filters, $query->getFilters());
        self::assertSame($order, $query->getOrder());
    }

    /**
     * @covers ::denormalize
     */
    public function testDenormalizeEmpty(): void
    {
        $denormalizer = new CollectionQueryDenormalizer();

        $request = new Request();

        $query = $denormalizer->denormalize($request, GetUsersQuery::class);

        self::assertSame(0, $query->getOffset());
        self::assertSame(100, $query->getLimit());
        self::assertNull($query->getSearch());
        self::assertSame([], $query->getFilters());
        self::assertSame([], $query->getOrder());
    }

    /**
     * @covers ::supportsDenormalization
     */
    public function testSupportsDenormalization(): void
    {
        $denormalizer = new CollectionQueryDenormalizer();

        self::assertTrue($denormalizer->supportsDenormalization(new Request(), GetUsersQuery::class));
        self::assertFalse($denormalizer->supportsDenormalization(new \stdClass(), GetUsersQuery::class));
        self::assertFalse($denormalizer->supportsDenormalization(new Request(), UpdateProfileCommand::class));
    }
}
