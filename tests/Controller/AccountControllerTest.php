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
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\AccountController
 */
final class AccountControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    /**
     * @covers ::getProfile
     */
    public function testGetProfile200(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/my/profile');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getProfile
     */
    public function testGetProfile401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/my/profile');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProfile
     */
    public function testUpdateProfile200(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'email'    => 'leland.doyle@example.com',
            'timezone' => 'America/Chicago',
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, '/api/my/profile', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProfile
     */
    public function testUpdateProfile400(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'email'    => 'leland.doyle@example',
            'timezone' => 'America/Chicago',
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, '/api/my/profile', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProfile
     */
    public function testUpdateProfile401(): void
    {
        $content = [
            'email'    => 'leland.doyle@example.com',
            'timezone' => 'America/Chicago',
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, '/api/my/profile', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateProfile
     */
    public function testUpdateProfile409(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'email'    => 'artem@example.com',
            'timezone' => 'America/Chicago',
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, '/api/my/profile', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword200(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'current' => 'secret',
            'new'     => 'p@ssw0rd',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, '/api/my/password', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword400WrongPassword(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [
            'current' => 'wrong',
            'new'     => 'p@ssw0rd',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, '/api/my/password', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword400EmptyContent(): void
    {
        $this->loginUser('ldoyle@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, '/api/my/password', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword401(): void
    {
        $content = [
            'current' => 'secret',
            'new'     => 'p@ssw0rd',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, '/api/my/password', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword403(): void
    {
        $this->loginUser('einstein@ldap.forumsys.com');

        $content = [
            'current' => 'secret',
            'new'     => 'p@ssw0rd',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, '/api/my/password', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplates
     */
    public function testGetTemplates200(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/my/templates');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTemplates
     */
    public function testGetTemplates401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/my/templates');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
