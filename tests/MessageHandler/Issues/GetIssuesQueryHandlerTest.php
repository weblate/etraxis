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

namespace App\MessageHandler\Issues;

use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\State;
use App\Entity\Template;
use App\Entity\User;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Issues\GetIssuesQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Issues\GetIssuesQueryHandler
 */
final class GetIssuesQueryHandlerTest extends WebTestCase
{
    use LoginTrait;

    private KernelBrowser     $client;
    private ManagerRegistry   $doctrine;
    private QueryBusInterface $queryBus;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client   = self::createClient();
        $this->doctrine = self::getContainer()->get('doctrine');
        $this->queryBus = self::getContainer()->get(QueryBusInterface::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testDefault(): void
    {
        $this->loginUser('ldoyle@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(42, $collection->getTotal());

        $repository = $this->doctrine->getRepository(Issue::class);

        $expected = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $repository->findAll());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testQueryByDeveloperB(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testQueryBySupportB(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('vparker@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(18, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testQueryByClientB(): void
    {
        $this->loginUser('aschinner@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(0, $collection->getTotal());
        self::assertCount(0, $collection->getItems());
    }

    /**
     * @covers ::__invoke
     */
    public function testQueryByAuthor(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('lucas.oconnell@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(6, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testQueryByResponsible(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Development task 8'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('tmarquardt@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(19, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset(): void
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Support request 1'],
            ['Molestiae', 'Support request 2'],
            ['Molestiae', 'Support request 3'],
            ['Molestiae', 'Development task 8'],
            ['Molestiae', 'Support request 4'],
            ['Molestiae', 'Support request 5'],
            ['Molestiae', 'Support request 6'],
            ['Excepturi', 'Support request 1'],
            ['Excepturi', 'Support request 2'],
            ['Excepturi', 'Support request 3'],
            ['Excepturi', 'Support request 4'],
            ['Excepturi', 'Support request 5'],
            ['Excepturi', 'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $query = new GetIssuesQuery(10, AbstractCollectionQuery::MAX_LIMIT, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
        ];

        $this->loginUser('amarvin@example.com');

        $query = new GetIssuesQuery(0, 10, null, [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'pOr', [], [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ]);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(20, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByFullId(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy([], ['id' => 'ASC']);

        $id = (int) mb_substr($issue->getFullId(), mb_strpos($issue->getFullId(), '-') + 1, -1) + 1;

        $expected = range($id * 10, $id * 10 + 9);

        $filters = [
            GetIssuesQuery::ISSUE_ID => '-'.mb_substr('00'.$id, -max(2, mb_strlen($id))),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(10, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => $issue->getId(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterBySubject(): void
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_SUBJECT => 'aSk',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectId(): void
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Support request 1'],
            ['Molestiae', 'Support request 2'],
            ['Molestiae', 'Support request 3'],
            ['Molestiae', 'Development task 8'],
            ['Molestiae', 'Support request 4'],
            ['Molestiae', 'Support request 5'],
            ['Molestiae', 'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Molestiae']);

        $filters = [
            GetIssuesQuery::ISSUE_PROJECT => $project->getId(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(14, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByProjectName(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_PROJECT_NAME => 'Ti',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(20, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplateId(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $filters = [
            GetIssuesQuery::ISSUE_TEMPLATE => $template->getId(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(6, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByTemplateName(): void
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_TEMPLATE_NAME => 'vELo',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByStateId(): void
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $filters = [
            GetIssuesQuery::ISSUE_STATE => $state->getId(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByStateName(): void
    {
        $expected = [
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_STATE_NAME => 'tED',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(7, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getName(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByAuthorId(): void
    {
        $expected = [
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var \App\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);
        $user       = $repository->findOneByEmail('labshire@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_AUTHOR => $user->getId(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByAuthorName(): void
    {
        $expected = [
            ['Carson Legros', 'Distinctio', 'Support request 2'],
            ['Carson Legros', 'Distinctio', 'Support request 3'],
            ['Carson Legros', 'Distinctio', 'Support request 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 6'],
            ['Carson Legros', 'Molestiae',  'Support request 2'],
            ['Carson Legros', 'Molestiae',  'Support request 3'],
            ['Carson Legros', 'Molestiae',  'Support request 5'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_AUTHOR_NAME => 'caR',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(8, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getAuthor()->getFullname(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleId(): void
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $filters = [
            GetIssuesQuery::ISSUE_RESPONSIBLE => $user->getId(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(3, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleNull(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_RESPONSIBLE => null,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(15, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByResponsibleName(): void
    {
        $expected = [
            ['Jarrell Kiehn',   'Distinctio', 'Support request 4'],
            ['Tracy Marquardt', 'Distinctio', 'Support request 5'],
            ['Tracy Marquardt', 'Molestiae',  'Support request 4'],
            ['Tracy Marquardt', 'Excepturi',  'Support request 2'],
            ['Carter Batz',     'Excepturi',  'Support request 4'],
            ['Carter Batz',     'Excepturi',  'Support request 5'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_RESPONSIBLE_NAME => 'AR',
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(6, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getResponsible()->getFullname(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsClonedYes(): void
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CLONED => true,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsClonedNo(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CLONED => false,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(25, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsCriticalYes(): void
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CRITICAL => true,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(12, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsCriticalNo(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CRITICAL => false,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(14, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsSuspendedYes(): void
    {
        $expected = [
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_SUSPENDED => true,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsSuspendedNo(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_SUSPENDED => false,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(22, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsClosedYes(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CLOSED => true,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(10, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByIsClosedNo(): void
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $filters = [
            GetIssuesQuery::ISSUE_IS_CLOSED => false,
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(16, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilter
     */
    public function testFilterByAge(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginUser('amarvin@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $filters = [
            GetIssuesQuery::ISSUE_AGE => $issue->getAge(),
        ];

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(7, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortById(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortBySubject(): void
    {
        $expected = [
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Development task 8'],
            ['Distinctio', 'Support request 1'],
            ['Molestiae',  'Support request 1'],
            ['Excepturi',  'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Excepturi',  'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Support request 4'],
            ['Excepturi',  'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_SUBJECT => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID      => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_PROJECT => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID      => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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
            ['Development', 'Molestiae',  'Development task 1'],
            ['Development', 'Molestiae',  'Development task 2'],
            ['Development', 'Molestiae',  'Development task 3'],
            ['Development', 'Molestiae',  'Development task 4'],
            ['Development', 'Molestiae',  'Development task 5'],
            ['Development', 'Molestiae',  'Development task 6'],
            ['Development', 'Molestiae',  'Development task 7'],
            ['Development', 'Molestiae',  'Development task 8'],
            ['Support',     'Distinctio', 'Support request 1'],
            ['Support',     'Distinctio', 'Support request 2'],
            ['Support',     'Distinctio', 'Support request 3'],
            ['Support',     'Distinctio', 'Support request 4'],
            ['Support',     'Distinctio', 'Support request 5'],
            ['Support',     'Distinctio', 'Support request 6'],
            ['Support',     'Molestiae',  'Support request 1'],
            ['Support',     'Molestiae',  'Support request 2'],
            ['Support',     'Molestiae',  'Support request 3'],
            ['Support',     'Molestiae',  'Support request 4'],
            ['Support',     'Molestiae',  'Support request 5'],
            ['Support',     'Molestiae',  'Support request 6'],
            ['Support',     'Excepturi',  'Support request 1'],
            ['Support',     'Excepturi',  'Support request 2'],
            ['Support',     'Excepturi',  'Support request 3'],
            ['Support',     'Excepturi',  'Support request 4'],
            ['Support',     'Excepturi',  'Support request 5'],
            ['Support',     'Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_TEMPLATE => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID       => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getName(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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
            ['Assigned',   'Molestiae',  'Development task 2'],
            ['Assigned',   'Molestiae',  'Development task 8'],
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['New',        'Molestiae',  'Development task 5'],
            ['New',        'Molestiae',  'Development task 6'],
            ['Opened',     'Distinctio', 'Support request 2'],
            ['Opened',     'Distinctio', 'Support request 4'],
            ['Opened',     'Distinctio', 'Support request 5'],
            ['Opened',     'Molestiae',  'Support request 2'],
            ['Opened',     'Molestiae',  'Support request 4'],
            ['Opened',     'Molestiae',  'Support request 5'],
            ['Opened',     'Excepturi',  'Support request 2'],
            ['Opened',     'Excepturi',  'Support request 4'],
            ['Opened',     'Excepturi',  'Support request 5'],
            ['Resolved',   'Distinctio', 'Support request 1'],
            ['Resolved',   'Distinctio', 'Support request 3'],
            ['Resolved',   'Molestiae',  'Support request 1'],
            ['Resolved',   'Molestiae',  'Support request 3'],
            ['Resolved',   'Excepturi',  'Support request 1'],
            ['Resolved',   'Excepturi',  'Support request 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_STATE => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID    => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getName(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByAuthor(): void
    {
        $expected = [
            ['Ansel Koepp',      'Molestiae',  'Development task 3'],
            ['Carolyn Hill',     'Molestiae',  'Development task 5'],
            ['Carolyn Hill',     'Molestiae',  'Development task 6'],
            ['Carson Legros',    'Distinctio', 'Support request 2'],
            ['Carson Legros',    'Distinctio', 'Support request 3'],
            ['Carson Legros',    'Distinctio', 'Support request 5'],
            ['Carson Legros',    'Molestiae',  'Support request 2'],
            ['Carson Legros',    'Molestiae',  'Support request 3'],
            ['Carson Legros',    'Molestiae',  'Support request 5'],
            ['Derrick Tillman',  'Molestiae',  'Support request 4'],
            ['Derrick Tillman',  'Excepturi',  'Support request 4'],
            ['Dorcas Ernser',    'Molestiae',  'Development task 2'],
            ['Jarrell Kiehn',    'Molestiae',  'Development task 4'],
            ['Jeramy Mueller',   'Distinctio', 'Support request 4'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 2'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 3'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 5'],
            ['Leland Doyle',     'Molestiae',  'Development task 1'],
            ['Lola Abshire',     'Molestiae',  'Development task 7'],
            ['Lola Abshire',     'Molestiae',  'Development task 8'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 1'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 6'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 1'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 6'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 1'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_AUTHOR => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID     => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getAuthor()->getFullname(),
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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
            [null,               'Distinctio', 'Support request 1'],
            [null,               'Distinctio', 'Support request 3'],
            [null,               'Molestiae',  'Development task 1'],
            [null,               'Molestiae',  'Development task 3'],
            [null,               'Distinctio', 'Support request 6'],
            [null,               'Molestiae',  'Development task 4'],
            [null,               'Molestiae',  'Development task 5'],
            [null,               'Molestiae',  'Development task 6'],
            [null,               'Molestiae',  'Development task 7'],
            [null,               'Molestiae',  'Support request 1'],
            [null,               'Molestiae',  'Support request 3'],
            [null,               'Molestiae',  'Support request 6'],
            [null,               'Excepturi',  'Support request 1'],
            [null,               'Excepturi',  'Support request 3'],
            [null,               'Excepturi',  'Support request 6'],
            ['Ansel Koepp',      'Molestiae',  'Development task 2'],
            ['Carter Batz',      'Excepturi',  'Support request 4'],
            ['Carter Batz',      'Excepturi',  'Support request 5'],
            ['Jarrell Kiehn',    'Distinctio', 'Support request 4'],
            ['Kailyn Bahringer', 'Molestiae',  'Support request 5'],
            ['Nikko Hills',      'Distinctio', 'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Development task 8'],
            ['Tracy Marquardt',  'Distinctio', 'Support request 5'],
            ['Tracy Marquardt',  'Molestiae',  'Support request 4'],
            ['Tracy Marquardt',  'Excepturi',  'Support request 2'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_RESPONSIBLE => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID          => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getResponsible() ? $issue->getResponsible()->getFullname() : null,
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByCreatedAt(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_CREATED_AT => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID         => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByChangedAt(): void
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_CHANGED_AT => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID         => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByClosedAt(): void
    {
        $expected = [
            // opened
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            // closed
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_CLOSED_AT => AbstractCollectionQuery::SORT_ASC,
            GetIssuesQuery::ISSUE_ID        => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByAge(): void
    {
        $expected = [
            ['Distinctio', 'Support request 2'],    // 1057 days
            ['Distinctio', 'Support request 4'],    //  946 days
            ['Distinctio', 'Support request 5'],    //  933 days
            ['Molestiae',  'Development task 2'],   //  725 days
            ['Distinctio', 'Support request 6'],    //  693 days
            ['Molestiae',  'Development task 5'],   //  661 days
            ['Molestiae',  'Development task 6'],   //  606 days
            ['Molestiae',  'Support request 2'],    //  553 days
            ['Molestiae',  'Development task 8'],   //  518 days
            ['Molestiae',  'Support request 6'],    //  512 days
            ['Molestiae',  'Support request 4'],    //  494 days
            ['Molestiae',  'Support request 5'],    //  482 days
            ['Excepturi',  'Support request 2'],    //  410 days
            ['Excepturi',  'Support request 4'],    //  366 days
            ['Excepturi',  'Support request 5'],    //  348 days
            ['Excepturi',  'Support request 6'],    //  345 days
            ['Molestiae',  'Development task 3'],   //    5 days
            ['Molestiae',  'Development task 1'],   //    3 days
            ['Distinctio', 'Support request 1'],    //    2 days
            ['Distinctio', 'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 7'],   //    2 days
            ['Molestiae',  'Support request 1'],    //    2 days
            ['Molestiae',  'Support request 3'],    //    2 days
            ['Excepturi',  'Support request 1'],    //    2 days
            ['Excepturi',  'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 4'],   //    1 day
        ];

        $this->loginUser('amarvin@example.com');

        $order = [
            GetIssuesQuery::ISSUE_AGE => AbstractCollectionQuery::SORT_DESC,
            GetIssuesQuery::ISSUE_ID  => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(26, $collection->getTotal());

        $actual = array_map(fn (Issue $issue) => [
            $issue->getState()->getTemplate()->getProject()->getName(),
            $issue->getSubject(),
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

        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
