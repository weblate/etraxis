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

namespace App\MessageHandler\Fields;

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\Project;
use App\Entity\State;
use App\Entity\Template;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Fields\GetFieldsQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Fields\GetFieldsQueryHandler
 */
final class GetFieldsQueryHandlerTest extends WebTestCase
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

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Field::class);

        $expected = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $repository->findBy(['removedAt' => null]));

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
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
            'Effort',
            'Issue ID',
            'New feature',
            'Priority',
            'Test coverage',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(35, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => $field->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            'Commit ID',
            'Delta',
            'Description',
            'Details',
            'Due date',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 5, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => $field->getName(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch(): void
    {
        $expected = [
            ['Effort',   'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Effort',   'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Effort',   'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Effort',   'Presto'],
            ['Priority', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'oR', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByProjectId
     */
    public function testFilterByProject(): void
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Project::class);
        $project    = $repository->findOneBy(['name' => 'Distinctio']);

        $filters = [
            GetFieldsQuery::FIELD_PROJECT => $project->getId(),
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(10, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByProjectId
     */
    public function testFilterByProjectNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_PROJECT => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByTemplateId
     */
    public function testFilterByTemplate(): void
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(Template::class);
        [$template] = $repository->findBy(['name' => 'Development']);

        $filters = [
            GetFieldsQuery::FIELD_TEMPLATE => $template->getId(),
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(9, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByTemplateId
     */
    public function testFilterByTemplateNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_TEMPLATE => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByStateId
     */
    public function testFilterByState(): void
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Priority',    'Distinctio'],
        ];

        $this->loginUser('admin@example.com');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(State::class);
        [$state]    = $repository->findBy(['name' => 'New']);

        $filters = [
            GetFieldsQuery::FIELD_STATE => $state->getId(),
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByStateId
     */
    public function testFilterByStateNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_STATE => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByName
     */
    public function testFilterByName(): void
    {
        $expected = [
            ['Due date',    'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Due date',    'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Due date',    'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_NAME => 'aT',
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

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
            GetFieldsQuery::FIELD_NAME => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByType
     */
    public function testFilterByType(): void
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['Details',     'Distinctio'],
            ['Description', 'Excepturi'],
            ['Details',     'Excepturi'],
            ['Description', 'Molestiae'],
            ['Details',     'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_TYPE => FieldTypeEnum::Text->value,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByType
     */
    public function testFilterByTypeNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_TYPE => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

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
            ['Delta', 'Distinctio'],
            ['Delta', 'Excepturi'],
            ['Delta', 'Molestiae'],
            ['Delta', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_DESCRIPTION => 'LoC',
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByDescription
     */
    public function testFilterByDescriptionNull(): void
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
            ['Issue ID',      'Excepturi'],
            ['New feature',   'Excepturi'],
            ['Priority',      'Excepturi'],
            ['Test coverage', 'Excepturi'],
            ['Commit ID',     'Molestiae'],
            ['Description',   'Molestiae'],
            ['Details',       'Molestiae'],
            ['Due date',      'Molestiae'],
            ['Issue ID',      'Molestiae'],
            ['New feature',   'Molestiae'],
            ['Priority',      'Molestiae'],
            ['Test coverage', 'Molestiae'],
            ['Commit ID',     'Presto'],
            ['Description',   'Presto'],
            ['Details',       'Presto'],
            ['Due date',      'Presto'],
            ['Issue ID',      'Presto'],
            ['New feature',   'Presto'],
            ['Priority',      'Presto'],
            ['Test coverage', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_DESCRIPTION => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(32, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByPosition
     */
    public function testFilterByPosition(): void
    {
        $expected = [
            ['Effort',      'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Effort',      'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Effort',      'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Effort',      'Presto'],
            ['New feature', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_POSITION => 3,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByPosition
     */
    public function testFilterByPositionNull(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_POSITION => null,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByIsRequired
     */
    public function testFilterByRequired(): void
    {
        $expected = [
            ['Delta',    'Distinctio'],
            ['Details',  'Distinctio'],
            ['Effort',   'Distinctio'],
            ['Issue ID', 'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Delta',    'Excepturi'],
            ['Details',  'Excepturi'],
            ['Effort',   'Excepturi'],
            ['Issue ID', 'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Delta',    'Molestiae'],
            ['Details',  'Molestiae'],
            ['Effort',   'Molestiae'],
            ['Issue ID', 'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Delta',    'Presto'],
            ['Details',  'Presto'],
            ['Effort',   'Presto'],
            ['Issue ID', 'Presto'],
            ['Priority', 'Presto'],
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetFieldsQuery::FIELD_IS_REQUIRED => true,
        ];

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(20, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

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

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProject(): void
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Delta',         'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
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
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_TEMPLATE => AbstractCollectionQuery::SORT_DESC,
            GetFieldsQuery::FIELD_NAME     => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT  => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByState(): void
    {
        $expected = [
            ['Due date',  'Distinctio'],
            ['Due date',  'Excepturi'],
            ['Due date',  'Molestiae'],
            ['Due date',  'Presto'],
            ['Commit ID', 'Distinctio'],
            ['Commit ID', 'Excepturi'],
            ['Commit ID', 'Molestiae'],
            ['Commit ID', 'Presto'],
            ['Delta',     'Distinctio'],
            ['Delta',     'Excepturi'],
            ['Delta',     'Molestiae'],
            ['Delta',     'Presto'],
            ['Effort',    'Distinctio'],
            ['Effort',    'Excepturi'],
            ['Effort',    'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_STATE   => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
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
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
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
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_TYPE    => AbstractCollectionQuery::SORT_DESC,
            GetFieldsQuery::FIELD_NAME    => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
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
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Effort',      'Distinctio'],
            ['Effort',      'Excepturi'],
            ['Effort',      'Molestiae'],
            ['Effort',      'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_DESCRIPTION => AbstractCollectionQuery::SORT_DESC,
            GetFieldsQuery::FIELD_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT     => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByPosition(): void
    {
        $expected = [
            ['Test coverage', 'Distinctio'],
            ['Test coverage', 'Excepturi'],
            ['Test coverage', 'Molestiae'],
            ['Test coverage', 'Presto'],
            ['Effort',        'Distinctio'],
            ['Effort',        'Excepturi'],
            ['Effort',        'Molestiae'],
            ['Effort',        'Presto'],
            ['New feature',   'Distinctio'],
            ['New feature',   'Excepturi'],
            ['New feature',   'Molestiae'],
            ['New feature',   'Presto'],
            ['Delta',         'Distinctio'],
            ['Delta',         'Excepturi'],
            ['Delta',         'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_POSITION => AbstractCollectionQuery::SORT_DESC,
            GetFieldsQuery::FIELD_NAME     => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT  => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByRequired(): void
    {
        $expected = [
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Due date',    'Distinctio'],
            ['Due date',    'Excepturi'],
            ['Due date',    'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Distinctio'],
            ['New feature', 'Excepturi'],
            ['New feature', 'Molestiae'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetFieldsQuery::FIELD_IS_REQUIRED => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_NAME        => AbstractCollectionQuery::SORT_ASC,
            GetFieldsQuery::FIELD_PROJECT     => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetFieldsQuery(0, 15, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());

        $actual = array_map(fn (Field $field) => [
            $field->getName(),
            $field->getState()->getTemplate()->getProject()->getName(),
        ], $collection->getItems());

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

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(40, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have required permissions.');

        $this->loginUser('artem@example.com');

        $query = new GetFieldsQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
