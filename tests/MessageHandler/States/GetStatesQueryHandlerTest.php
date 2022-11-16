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

namespace App\MessageHandler\States;

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\Project;
use App\Entity\State;
use App\Entity\Template;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\States\GetStatesQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\States\GetStatesQueryHandler
 */
final class GetStatesQueryHandlerTest extends WebTestCase
{
    use LoginTrait;

    private KernelBrowser     $client;
    private QueryBusInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client   = self::createClient();
        $this->queryBus = self::getContainer()->get(QueryBusInterface::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testDefault(): void
    {
        $this->loginUser('admin@example.com');

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(State::class);

        $expected = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $repository->findAll());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset(): void
    {
        $expected = [
            'Opened',
            'Resolved',
            'Submitted',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(25, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => $state->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            'Assigned',
            'Completed',
            'Duplicated',
            'New',
            'Opened',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, 5, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => $state->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch(): void
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['Opened',   'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'NEd', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProject(): void
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['New',        'Distinctio'],
            ['Opened',     'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Submitted',  'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Project::class);
        $project    = $repository->findOneBy(['name' => 'Distinctio']);

        $filters = [
            GetStatesQuery::STATE_PROJECT => $project->getId(),
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(7, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_PROJECT => null,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplate(): void
    {
        $expected = [
            ['Opened',    'Distinctio'],
            ['Resolved',  'Distinctio'],
            ['Submitted', 'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Template::class);
        [$template] = $repository->findBy(['name' => 'Support']);

        $filters = [
            GetStatesQuery::STATE_TEMPLATE => $template->getId(),
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplateNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_TEMPLATE => null,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName(): void
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['New',      'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['New',      'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['New',      'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['New',      'Presto'],
            ['Opened',   'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_NAME => 'nE',
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(12, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByNameNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_NAME => null,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByType(): void
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Duplicated', 'Excepturi'],
            ['Resolved',   'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_TYPE => StateTypeEnum::Final->value,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(9, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTypeNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_TYPE => null,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsible(): void
    {
        $expected = [
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
            ['Submitted', 'Molestiae'],
            ['Submitted', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_RESPONSIBLE => StateResponsibleEnum::Keep->value,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetStatesQuery::STATE_RESPONSIBLE => null,
        ];

        $order = [
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject(): void
    {
        $expected = [
            ['New',        'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Assigned',   'Distinctio'],
            ['Submitted',  'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Opened',     'Distinctio'],
            ['New',        'Excepturi'],
            ['Duplicated', 'Excepturi'],
            ['Completed',  'Excepturi'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_PROJECT  => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_TEMPLATE => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME     => AbstractCollectionQuery::SORT_DESC,
        ];

        $query = new GetStatesQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByTemplate(): void
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_TEMPLATE => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME     => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_PROJECT  => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByName(): void
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByType(): void
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Distinctio'],
            ['Resolved',   'Excepturi'],
            ['Resolved',   'Molestiae'],
            ['New',        'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_TYPE    => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME    => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByResponsible(): void
    {
        $expected = [
            ['Assigned',  'Distinctio'],
            ['Assigned',  'Excepturi'],
            ['Assigned',  'Molestiae'],
            ['Assigned',  'Presto'],
            ['Opened',    'Distinctio'],
            ['Opened',    'Excepturi'],
            ['Opened',    'Molestiae'],
            ['Opened',    'Presto'],
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetStatesQuery::STATE_RESPONSIBLE => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetStatesQuery::STATE_PROJECT     => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetStatesQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(28, $collection->getTotal());

        $actual = array_map(fn (State $state) => [
            $state->getName(),
            $state->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have required permissions.');

        $this->loginUser('artem@example.com');

        $query = new GetStatesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
