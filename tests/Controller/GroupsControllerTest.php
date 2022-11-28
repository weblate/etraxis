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

namespace App\Controller;

use App\Entity\Group;
use App\LoginTrait;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\GroupsController
 */
final class GroupsControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private GroupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    /**
     * @covers ::listGroups
     */
    public function testListGroups200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/groups');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listGroups
     */
    public function testListGroups401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/groups');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listGroups
     */
    public function testListGroups403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/groups');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createGroup
     */
    public function testCreateGroup201(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'project'     => null,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/groups', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createGroup
     */
    public function testCreateGroup400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/groups', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createGroup
     */
    public function testCreateGroup401(): void
    {
        $content = [
            'project'     => null,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/groups', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createGroup
     */
    public function testCreateGroup403(): void
    {
        $this->loginUser('artem@example.com');

        $content = [
            'project'     => null,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/groups', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createGroup
     */
    public function testCreateGroup409(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'project'     => null,
            'name'        => 'Company Staff',
            'description' => 'Test Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/groups', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroup
     */
    public function testGetGroup200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroup
     */
    public function testGetGroup401(): void
    {
        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroup
     */
    public function testGetGroup403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroup
     */
    public function testGetGroup404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/groups/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', $group->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', $group->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup401(): void
    {
        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', $group->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', $group->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateGroup
     */
    public function testUpdateGroup409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Managers',
            'description' => 'Software Engineers',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/groups/%s', $group->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteGroup
     */
    public function testDeleteGroup200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteGroup
     */
    public function testDeleteGroup401(): void
    {
        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteGroup
     */
    public function testDeleteGroup403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/groups/%s', $group->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
