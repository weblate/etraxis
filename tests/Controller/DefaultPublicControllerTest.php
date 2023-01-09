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

use App\LoginTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\DefaultPublicController
 */
final class DefaultPublicControllerTest extends WebTestCase
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
     * @covers ::settings
     * @covers ::timezones
     */
    public function testAnonymous(): void
    {
        $this->client->request(Request::METHOD_GET, '/');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->request(Request::METHOD_GET, '/settings');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->request(Request::METHOD_GET, '/timezones');
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    /**
     * @covers ::index
     * @covers ::settings
     * @covers ::timezones
     */
    public function testUser(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->request(Request::METHOD_GET, '/');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, '/settings');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, '/timezones');
        self::assertTrue($this->client->getResponse()->isOk());
    }
}
