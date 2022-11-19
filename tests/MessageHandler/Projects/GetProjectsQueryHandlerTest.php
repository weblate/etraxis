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

namespace App\MessageHandler\Projects;

use App\Entity\Project;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Projects\GetProjectsQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Projects\GetProjectsQueryHandler
 */
final class GetProjectsQueryHandlerTest extends WebTestCase
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

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Project::class);

        $expected = array_map(fn (Project $project) => $project->getName(), $repository->findAll());
        $actual   = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

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
            'Molestiae',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(2, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            'Distinctio',
            'Excepturi',
            'Molestiae',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, 3, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch(): void
    {
        $expected = [
            'Molestiae',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'eSt', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByName
     */
    public function testFilterByName(): void
    {
        $expected = [
            'Distinctio',
            'Molestiae',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_NAME => 'Ti',
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByName
     */
    public function testFilterByNameNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_NAME => null,
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByDescription
     */
    public function testFilterByDescription(): void
    {
        $expected = [
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_DESCRIPTION => ' d',
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByDescription
     */
    public function testFilterByDescriptionNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_DESCRIPTION => null,
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByIsSuspended
     */
    public function testFilterBySuspended(): void
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_IS_SUSPENDED => false,
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByIsSuspended
     * @covers ::queryFilterByName
     */
    public function testCombinedFilter(): void
    {
        $expected = [
            'Excepturi',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetProjectsQuery::PROJECT_NAME         => 'R',
            GetProjectsQuery::PROJECT_IS_SUSPENDED => false,
        ];

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testFilterByUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            'unknown' => null,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByName(): void
    {
        $expected = [
            'Distinctio',
            'Excepturi',
            'Molestiae',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_NAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription(): void
    {
        $expected = [
            'Distinctio',
            'Molestiae',
            'Excepturi',
            'Presto',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByCreated(): void
    {
        $expected = [
            'Presto',
            'Excepturi',
            'Molestiae',
            'Distinctio',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_CREATED_AT => AbstractCollectionQuery::SORT_DESC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortBySuspended(): void
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
            'Distinctio',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetProjectsQuery::PROJECT_IS_SUSPENDED => AbstractCollectionQuery::SORT_ASC,
            GetProjectsQuery::PROJECT_NAME         => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Project $project) => $project->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $order = [
            'unknown' => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have required permissions.');

        $this->loginUser('artem@example.com');

        $query = new GetProjectsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
