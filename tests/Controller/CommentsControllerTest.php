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
 * @coversDefaultClass \App\Controller\CommentsController
 */
final class CommentsControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::listComments
     */
    public function testListComments200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/comments', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listComments
     */
    public function testListComments401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/comments', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listComments
     */
    public function testListComments403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/comments', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listComments
     */
    public function testListComments404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/comments', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addComment
     */
    public function testAddComment200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $content = [
            'body'    => 'Test comment.',
            'private' => false,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/comments', $issue->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addComment
     */
    public function testAddComment400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/comments', $issue->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addComment
     */
    public function testAddComment401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $content = [
            'body'    => 'Test comment.',
            'private' => false,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/comments', $issue->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addComment
     */
    public function testAddComment403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $content = [
            'body'    => 'Test comment.',
            'private' => false,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/comments', $issue->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addComment
     */
    public function testAddComment404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'body'    => 'Test comment.',
            'private' => false,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/comments', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
