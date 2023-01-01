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

use App\Entity\Project;
use App\LoginTrait;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\ProjectsController
 */
final class ProjectsControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ProjectRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    /**
     * @covers ::listProjects
     */
    public function testListProjects200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/projects');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listProjects
     */
    public function testListProjects401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/projects');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listProjects
     */
    public function testListProjects403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/projects');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createProject
     */
    public function testCreateProject201(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/projects', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createProject
     */
    public function testCreateProject400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/projects', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createProject
     */
    public function testCreateProject401(): void
    {
        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/projects', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createProject
     */
    public function testCreateProject403(): void
    {
        $this->loginUser('artem@example.com');

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/projects', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createProject
     */
    public function testCreateProject409(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Distinctio',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/projects', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getProject
     */
    public function testGetProject200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getProject
     */
    public function testGetProject401(): void
    {
        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getProject
     */
    public function testGetProject403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getProject
     */
    public function testGetProject404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/projects/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', $project->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', $project->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject401(): void
    {
        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', $project->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', $project->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProject
     */
    public function testUpdateProject409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $content = [
            'name'        => 'Molestiae',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/projects/%s', $project->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteProject
     */
    public function testDeleteProject200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Presto']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteProject
     */
    public function testDeleteProject401(): void
    {
        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Presto']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteProject
     */
    public function testDeleteProject403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Presto']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/projects/%s', $project->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendProject
     */
    public function testSuspendProject200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/suspend', $project->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendProject
     */
    public function testSuspendProject401(): void
    {
        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/suspend', $project->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendProject
     */
    public function testSuspendProject403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/suspend', $project->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::suspendProject
     */
    public function testSuspendProject404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/suspend', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeProject
     */
    public function testResumeProject200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/resume', $project->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeProject
     */
    public function testResumeProject401(): void
    {
        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/resume', $project->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeProject
     */
    public function testResumeProject403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/resume', $project->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::resumeProject
     */
    public function testResumeProject404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/projects/%s/resume', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
