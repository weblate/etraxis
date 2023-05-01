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
use App\Entity\User;
use App\LoginTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\DefaultAdminController
 */
final class DefaultAdminControllerTest extends WebTestCase
{
    use LoginTrait;

    private KernelBrowser $client;
    private ?ManagerRegistry $doctrine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $this->doctrine = self::getContainer()->get('doctrine');
    }

    /**
     * @covers ::groups
     * @covers ::index
     * @covers ::projects
     * @covers ::users
     * @covers ::viewProject
     * @covers ::viewUser
     */
    public function testAnonymous(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->request(Request::METHOD_GET, '/admin/users');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('artem@example.com');
        $this->client->request(Request::METHOD_GET, sprintf('/admin/users/%s', $user->getId()));
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->request(Request::METHOD_GET, '/admin/groups');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->request(Request::METHOD_GET, '/admin/projects');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);
        $this->client->request(Request::METHOD_GET, sprintf('/admin/projects/%s', $project->getId()));
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    /**
     * @covers ::groups
     * @covers ::index
     * @covers ::projects
     * @covers ::users
     * @covers ::viewProject
     * @covers ::viewUser
     */
    public function testUser(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->request(Request::METHOD_GET, '/admin');
        self::assertTrue($this->client->getResponse()->isForbidden());

        $this->client->request(Request::METHOD_GET, '/admin/users');
        self::assertTrue($this->client->getResponse()->isForbidden());

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('artem@example.com');
        $this->client->request(Request::METHOD_GET, sprintf('/admin/users/%s', $user->getId()));
        self::assertTrue($this->client->getResponse()->isForbidden());

        $this->client->request(Request::METHOD_GET, '/admin/groups');
        self::assertTrue($this->client->getResponse()->isForbidden());

        $this->client->request(Request::METHOD_GET, '/admin/projects');
        self::assertTrue($this->client->getResponse()->isForbidden());

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);
        $this->client->request(Request::METHOD_GET, sprintf('/admin/projects/%s', $project->getId()));
        self::assertTrue($this->client->getResponse()->isForbidden());
    }

    /**
     * @covers ::groups
     * @covers ::index
     * @covers ::projects
     * @covers ::users
     * @covers ::viewProject
     * @covers ::viewUser
     */
    public function testAdmin(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->request(Request::METHOD_GET, '/admin');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, '/admin/users');
        self::assertTrue($this->client->getResponse()->isOk());

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('artem@example.com');
        $this->client->request(Request::METHOD_GET, sprintf('/admin/users/%s', $user->getId()));
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, '/admin/groups');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, '/admin/projects');
        self::assertTrue($this->client->getResponse()->isOk());

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);
        $this->client->request(Request::METHOD_GET, sprintf('/admin/projects/%s', $project->getId()));
        self::assertTrue($this->client->getResponse()->isOk());
    }
}
