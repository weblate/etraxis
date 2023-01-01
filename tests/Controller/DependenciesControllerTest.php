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
 * @coversDefaultClass \App\Controller\DependenciesController
 */
final class DependenciesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::listDependencies
     */
    public function testListDependencies200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/dependencies', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listDependencies
     */
    public function testListDependencies401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/dependencies', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listDependencies
     */
    public function testListDependencies403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/dependencies', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listDependencies
     */
    public function testListDependencies404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/dependencies', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addDependency
     */
    public function testAddDependency200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addDependency
     */
    public function testAddDependency400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addDependency
     */
    public function testAddDependency401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addDependency
     */
    public function testAddDependency403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::addDependency
     */
    public function testAddDependency404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/issues/%s/dependencies/%s', self::UNKNOWN_ENTITY_ID, $dependency->getId()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeDependency
     */
    public function testRemoveDependency200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeDependency
     */
    public function testRemoveDependency401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeDependency
     */
    public function testRemoveDependency403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/dependencies/%s', $issue->getId(), $dependency->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::removeDependency
     */
    public function testRemoveDependency404(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $dependency */
        [/* skipping */ , /* skipping */ , $dependency] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/dependencies/%s', self::UNKNOWN_ENTITY_ID, $dependency->getId()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
