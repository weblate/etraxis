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

use App\LoginTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\DefaultAdminController
 */
final class DefaultAdminControllerTest extends WebTestCase
{
    use LoginTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();
    }

    /**
     * @covers ::index
     */
    public function testIndexAnonymous(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::index
     */
    public function testIndexUser(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->request(Request::METHOD_GET, '/admin');

        self::assertTrue($this->client->getResponse()->isForbidden());
    }

    /**
     * @covers ::index
     */
    public function testIndexAdmin(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->request(Request::METHOD_GET, '/admin');

        self::assertTrue($this->client->getResponse()->isOk());
    }
}
