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
 * @coversDefaultClass \App\Controller\WatchersController
 */
final class WatchersControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::watchIssues
     */
    public function testWatchIssues200(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/watch', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssues
     */
    public function testWatchIssues400(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
                'test',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/watch', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssues
     */
    public function testWatchIssues401(): void
    {
        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                $forbidden->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/watch', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unwatchIssues
     */
    public function testUnwatchIssues200(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unwatch', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unwatchIssues
     */
    public function testUnwatchIssues400(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                self::UNKNOWN_ENTITY_ID,
                'test',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unwatch', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unwatchIssues
     */
    public function testUnwatchIssues401(): void
    {
        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $content = [
            'issues' => [
                $watching->getId(),
                $unwatching->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/issues/unwatch', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getWatchers
     */
    public function testGetWatchers200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/watchers', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getWatchers
     */
    public function testGetWatchers401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/watchers', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getWatchers
     */
    public function testGetWatchers403(): void
    {
        $this->loginUser('hstroman@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/watchers', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getWatchers
     */
    public function testGetWatchers404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/watchers', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssue
     */
    public function testWatchIssue200(): void
    {
        $this->loginUser('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/watch', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssue
     */
    public function testWatchIssue401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/watch', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssue
     */
    public function testWatchIssue403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/watch', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::watchIssue
     */
    public function testWatchIssue404(): void
    {
        $this->loginUser('tmarquardt@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/watch', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unwatchIssue
     */
    public function testUnwatchIssue200(): void
    {
        $this->loginUser('fdooley@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/unwatch', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unwatchIssue
     */
    public function testUnwatchIssue401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/unwatch', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
