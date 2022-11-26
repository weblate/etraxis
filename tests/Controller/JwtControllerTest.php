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

use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\JwtController
 */
final class JwtControllerTest extends TransactionalTestCase
{
    /**
     * @covers ::login
     */
    public function testLogin200(): void
    {
        $content = [
            'email'    => 'artem@example.com',
            'password' => 'secret',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/login', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertArrayHasKey('token', json_decode($this->client->getResponse()->getContent(), true));
    }

    /**
     * @covers ::login
     */
    public function testLogin400(): void
    {
        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/login', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::login
     */
    public function testLogin404(): void
    {
        $content = [
            'email'    => 'artem@example.com',
            'password' => 'wrong',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/login', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
