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

namespace App\MessageHandler\Groups;

use App\Entity\Group;
use App\Entity\Project;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Groups\GetGroupsQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Groups\GetGroupsQueryHandler
 */
final class GetGroupsQueryHandlerTest extends WebTestCase
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

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Group::class);

        $expected = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $repository->findAll());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
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
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(10, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            ['Clients',         'Clients A'],
            ['Clients',         'Clients B'],
            ['Clients',         'Clients C'],
            ['Clients',         'Clients D'],
            ['Company Clients', null],
            ['Company Staff',   null],
            ['Developers',      'Developers A'],
            ['Developers',      'Developers B'],
            ['Developers',      'Developers C'],
            ['Developers',      'Developers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch(): void
    {
        $expected = [
            ['Clients',         'Clients A'],
            ['Clients',         'Clients B'],
            ['Clients',         'Clients C'],
            ['Clients',         'Clients D'],
            ['Company Clients', null],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'cliENTs', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(5, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
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
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Project::class);
        $project    = $repository->findOneBy(['name' => 'Distinctio']);

        $filters = [
            GetGroupsQuery::GROUP_PROJECT => $project->getId(),
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectNull(): void
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetGroupsQuery::GROUP_PROJECT => null,
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByName(): void
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetGroupsQuery::GROUP_NAME => 'eRS',
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(12, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
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
            GetGroupsQuery::GROUP_NAME => null,
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescription(): void
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetGroupsQuery::GROUP_DESCRIPTION => 'eRS a',
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescriptionNull(): void
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetGroupsQuery::GROUP_DESCRIPTION => null,
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByGlobal(): void
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetGroupsQuery::GROUP_IS_GLOBAL => false,
        ];

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(16, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject(): void
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
            ['Clients',           'Clients C'],
            ['Developers',        'Developers C'],
            ['Managers',          'Managers C'],
            ['Support Engineers', 'Support Engineers C'],
            ['Clients',           'Clients B'],
            ['Developers',        'Developers B'],
            ['Managers',          'Managers B'],
            ['Support Engineers', 'Support Engineers B'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_PROJECT     => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
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
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription(): void
    {
        $expected = [
            ['Company Staff',     null],
            ['Company Clients',   null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_DESC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByGlobal(): void
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetGroupsQuery::GROUP_IS_GLOBAL   => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetGroupsQuery::GROUP_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Group $group) => [
            $group->getName(),
            $group->getDescription(),
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

        $query = new GetGroupsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
