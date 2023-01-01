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

use App\Entity\Issue;
use App\LoginTrait;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\RelatedIssuesController
 */
final class RelatedIssuesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::listRelatedIssues
     */
    public function testListRelatedIssues200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/related', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listRelatedIssues
     */
    public function testListRelatedIssues401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/related', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listRelatedIssues
     */
    public function testListRelatedIssues403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/related', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listRelatedIssues
     */
    public function testListRelatedIssues404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/related', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addRelatedIssue
     */
    public function testAddRelatedIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addRelatedIssue
     */
    public function testAddRelatedIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addRelatedIssue
     */
    public function testAddRelatedIssue403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addRelatedIssue
     */
    public function testAddRelatedIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/related/%s', self::UNKNOWN_ENTITY_ID, $relatedIssue->getId()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeRelatedIssue
     */
    public function testRemoveRelatedIssue200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeRelatedIssue
     */
    public function testRemoveRelatedIssue401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeRelatedIssue
     */
    public function testRemoveRelatedIssue403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/related/%s', $issue->getId(), $relatedIssue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeRelatedIssue
     */
    public function testRemoveRelatedIssue404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $relatedIssue */
        [/* skipping */ , /* skipping */ , $relatedIssue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/related/%s', self::UNKNOWN_ENTITY_ID, $relatedIssue->getId()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
