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
 * @coversDefaultClass \App\Controller\LoginController
 */
final class LoginControllerTest extends WebTestCase
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
        $this->client->request(Request::METHOD_GET, '/login');

        self::assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers ::index
     */
    public function testIndexUser(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->request(Request::METHOD_GET, '/login');

        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateSuccess(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->request(Request::METHOD_POST, '/login');

        self::assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateFailure(): void
    {
        $this->client->request(Request::METHOD_POST, '/login');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        self::assertSame('Invalid credentials.', json_decode($this->client->getResponse()->getContent(), true));
    }
}
