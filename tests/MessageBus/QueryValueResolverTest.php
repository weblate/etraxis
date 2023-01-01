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

namespace App\MessageBus;

use App\Message\AbstractCollectionQuery;
use App\Message\Users\GetUsersQuery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\QueryValueResolver
 */
final class QueryValueResolverTest extends WebTestCase
{
    private QueryValueResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $serializer = self::getContainer()->get('serializer');

        $this->resolver = new QueryValueResolver($serializer);
    }

    /**
     * @covers ::resolve
     */
    public function testResolve(): void
    {
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

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('query', GetUsersQuery::class, false, false, null));
        $query     = $generator->current();

        self::assertInstanceOf(GetUsersQuery::class, $query);
        self::assertSame(50, $query->getOffset());
        self::assertSame(25, $query->getLimit());
        self::assertSame('test', $query->getSearch());
        self::assertSame($filters, $query->getFilters());
        self::assertSame($order, $query->getOrder());
    }
}
