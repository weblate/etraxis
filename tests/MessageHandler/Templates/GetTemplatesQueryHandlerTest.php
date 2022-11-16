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

namespace App\MessageHandler\Templates;

use App\Entity\Project;
use App\Entity\Template;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Templates\GetTemplatesQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Templates\GetTemplatesQueryHandler
 */
final class GetTemplatesQueryHandlerTest extends WebTestCase
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

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Template::class);

        $expected = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $repository->findAll());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(5, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, 5, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'd', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(5, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Project::class);
        $project    = $repository->findOneBy(['name' => 'Distinctio']);

        $filters = [
            GetTemplatesQuery::TEMPLATE_PROJECT => $project->getId(),
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            GetTemplatesQuery::TEMPLATE_PROJECT => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_NAME => 'eNT',
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            GetTemplatesQuery::TEMPLATE_NAME => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPrefix(): void
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_PREFIX => 'rEQ',
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByPrefixNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_PREFIX => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

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
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => ' d',
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByDescriptionNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByCriticalAge(): void
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => 3,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByCriticalAgeNull(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFrozenTime(): void
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_FROZEN_TIME => 7,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFrozenTimeNull(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_FROZEN_TIME => null,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByLocked(): void
    {
        $expected = [
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetTemplatesQuery::TEMPLATE_IS_LOCKED => true,
        ];

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Development', 'Development Task B'],
            ['Support',     'Support Request B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_NAME        => AbstractCollectionQuery::SORT_DESC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByPrefix(): void
    {
        $expected = [
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_PREFIX      => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByCriticalAge(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_CRITICAL_AGE => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION  => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByFrozenTime(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_FROZEN_TIME => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByLocked(): void
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetTemplatesQuery::TEMPLATE_IS_LOCKED   => AbstractCollectionQuery::SORT_ASC,
            GetTemplatesQuery::TEMPLATE_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Template $template) => [
            $template->getName(),
            $template->getDescription(),
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

        $query = new GetTemplatesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
