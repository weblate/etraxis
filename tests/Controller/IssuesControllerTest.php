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

namespace App\Controller;

use App\Entity\Enums\SecondsEnum;
use App\Entity\Field;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\Template;
use App\Entity\User;
use App\LoginTrait;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\IssuesController
 */
final class IssuesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::listIssues
     */
    public function testListIssues200(): void
    {
        $this->loginUser('fdooley@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/issues');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listIssues
     */
    public function testListIssues401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/issues');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createIssue
     */
    public function testCreateIssue201(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'New feature']);

        $content = [
            'template'    => $template->getId(),
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createIssue
     */
    public function testCreateIssue400(): void
    {
        $this->loginUser('nhills@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createIssue
     */
    public function testCreateIssue401(): void
    {
        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'New feature']);

        $content = [
            'template'    => $template->getId(),
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createIssue
     */
    public function testCreateIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $template->getInitialState(), 'name' => 'New feature']);

        $content = [
            'template'    => $template->getId(),
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadIssuesAsCsv
     */
    public function testDownloadIssuesAsCsv200(): void
    {
        $this->loginUser('fdooley@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/issues/csv');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadIssuesAsCsv
     */
    public function testDownloadIssuesAsCsv401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/issues/csv');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::readIssues
     */
    public function testReadIssues200(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/read', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::readIssues
     */
    public function testReadIssues400(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
                'test',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/read', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::readIssues
     */
    public function testReadIssues401(): void
    {
        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/read', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unreadIssues
     */
    public function testUnreadIssues200(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unread', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unreadIssues
     */
    public function testUnreadIssues400(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                self::UNKNOWN_ENTITY_ID,
                'test',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unread', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unreadIssues
     */
    public function testUnreadIssues401(): void
    {
        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $read->getId(),
                $unread->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unread', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getIssue
     */
    public function testGetIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getIssue
     */
    public function testGetIssue401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getIssue
     */
    public function testGetIssue403(): void
    {
        $this->loginUser('lucas.oconnell@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getIssue
     */
    public function testGetIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneIssue
     */
    public function testCloneIssue201(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'New feature']);

        $content = [
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::cloneIssue
     */
    public function testCloneIssue400(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneIssue
     */
    public function testCloneIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'New feature']);

        $content = [
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneIssue
     */
    public function testCloneIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'New feature']);

        $content = [
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneIssue
     */
    public function testCloneIssue404(): void
    {
        $this->loginUser('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field1 */
        $field1 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Priority']);

        /** @var Field $field2 */
        $field2 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Description']);

        /** @var Field $field3 */
        $field3 = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'New feature']);

        $content = [
            'subject'     => 'Test issue',
            'responsible' => null,
            'fields'      => [
                $field1->getId() => 2,
                $field2->getId() => 'Est dolorum omnis accusantium hic veritatis ut.',
                $field3->getId() => true,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateIssue
     */
    public function testUpdateIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'subject' => 'Test issue',
            'fields'  => null,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateIssue
     */
    public function testUpdateIssue400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->doctrine->getRepository(Field::class)->findOneBy(['state' => $issue->getTemplate()->getInitialState(), 'name' => 'Priority']);

        $content = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->getId() => 4,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateIssue
     */
    public function testUpdateIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'subject' => 'Test issue',
            'fields'  => null,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateIssue
     */
    public function testUpdateIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'subject' => 'Test issue',
            'fields'  => null,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/issues/%s', $issue->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateIssue
     */
    public function testUpdateIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'subject' => 'Test issue',
            'fields'  => null,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteIssue
     */
    public function testDeleteIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteIssue
     */
    public function testDeleteIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteIssue
     */
    public function testDeleteIssue403(): void
    {
        $this->loginUser('labshire@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteIssue
     */
    public function testDeleteIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::changeState
     */
    public function testChangeState200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $content = [
            'responsible' => $assignee->getId(),
            'fields'      => null,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/state/%s', $issue->getId(), $state->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::changeState
     */
    public function testChangeState400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $content = [
            'responsible' => $assignee->getId(),
            'fields'      => [
                self::UNKNOWN_ENTITY_ID => 'test',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/state/%s', $issue->getId(), $state->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::changeState
     */
    public function testChangeState401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $content = [
            'responsible' => $assignee->getId(),
            'fields'      => null,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/state/%s', $issue->getId(), $state->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::changeState
     */
    public function testChangeState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $content = [
            'responsible' => $assignee->getId(),
            'fields'      => null,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/state/%s', $issue->getId(), $state->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::changeState
     */
    public function testChangeState404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var State $state */
        [/* skipping */ , /* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $content = [
            'responsible' => $assignee->getId(),
            'fields'      => null,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/state/%s', self::UNKNOWN_ENTITY_ID, $state->getId()), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/assign/%s', $issue->getId(), $assignee->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/assign/%s', $issue->getId(), $assignee->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/assign/%s', $issue->getId(), $assignee->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::assignIssue
     */
    public function testAssignIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $assignee */
        $assignee = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/assign/%s', self::UNKNOWN_ENTITY_ID, $assignee->getId()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendIssue
     */
    public function testSuspendIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $content = [
            'date' => date('Y-m-d', time() + SecondsEnum::OneDay->value),
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/suspend', $issue->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendIssue
     */
    public function testSuspendIssue400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/suspend', $issue->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendIssue
     */
    public function testSuspendIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $content = [
            'date' => date('Y-m-d', time() + SecondsEnum::OneDay->value),
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/suspend', $issue->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendIssue
     */
    public function testSuspendIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $content = [
            'date' => date('Y-m-d', time() + SecondsEnum::OneDay->value),
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/suspend', $issue->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendIssue
     */
    public function testSuspendIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'date' => date('Y-m-d', time() + SecondsEnum::OneDay->value),
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/suspend', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeIssue
     */
    public function testResumeIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/resume', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeIssue
     */
    public function testResumeIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/resume', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeIssue
     */
    public function testResumeIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/resume', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeIssue
     */
    public function testResumeIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/resume', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
