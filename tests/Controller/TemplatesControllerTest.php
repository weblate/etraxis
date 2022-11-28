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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Group;
use App\Entity\Project;
use App\Entity\Template;
use App\LoginTrait;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\TemplatesController
 */
final class TemplatesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    /**
     * @covers ::listTemplates
     */
    public function testListTemplates200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/templates');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listTemplates
     */
    public function testListTemplates401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/templates');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listTemplates
     */
    public function testListTemplates403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/templates');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate201(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate401(): void
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createTemplate
     */
    public function testCreateTemplate409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/templates', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplate200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplate401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplate
     */
    public function testGetTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate201(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate401(): void
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::cloneTemplate
     */
    public function testCloneTemplate409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $content = [
            'project'     => $project->getId(),
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateTemplate
     */
    public function testUpdateTemplate409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Bugfix',
            'prefix'      => 'req',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s', $template->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteTemplate
     */
    public function testDeleteTemplate200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteTemplate
     */
    public function testDeleteTemplate401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteTemplate
     */
    public function testDeleteTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/templates/%s', $template->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::lockTemplate
     */
    public function testLockTemplate200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/lock', $template->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::lockTemplate
     */
    public function testLockTemplate401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/lock', $template->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::lockTemplate
     */
    public function testLockTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/lock', $template->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::lockTemplate
     */
    public function testLockTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/lock', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unlockTemplate
     */
    public function testUnlockTemplate200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/unlock', $template->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unlockTemplate
     */
    public function testUnlockTemplate401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/unlock', $template->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unlockTemplate
     */
    public function testUnlockTemplate403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/unlock', $template->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::unlockTemplate
     */
    public function testUnlockTemplate404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/templates/%s/unlock', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s/permissions', $template->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s/permissions', $template->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s/permissions', $template->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/templates/%s/permissions', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $content = [
            'permission' => TemplatePermissionEnum::PrivateComments,
            'roles'      => [
                SystemRoleEnum::Author,
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $group->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s/permissions', $template->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'permission' => TemplatePermissionEnum::PrivateComments,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s/permissions', $template->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions401(): void
    {
        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $content = [
            'permission' => TemplatePermissionEnum::PrivateComments,
            'roles'      => [
                SystemRoleEnum::Author,
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $group->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s/permissions', $template->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $content = [
            'permission' => TemplatePermissionEnum::PrivateComments,
            'roles'      => [
                SystemRoleEnum::Author,
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $group->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s/permissions', $template->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions404(): void
    {
        $this->loginUser('admin@example.com');

        /** Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $content = [
            'permission' => TemplatePermissionEnum::PrivateComments,
            'roles'      => [
                SystemRoleEnum::Author,
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $group->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/templates/%s/permissions', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
