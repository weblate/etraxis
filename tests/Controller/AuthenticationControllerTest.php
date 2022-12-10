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

use App\Entity\User;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\AuthenticationController
 */
final class AuthenticationControllerTest extends TransactionalTestCase
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
    public function testLogin404(): void
    {
        $content = [
            'email'    => 'artem@example.com',
            'password' => 'wrong',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/login', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::forgot
     */
    public function testForgot200(): void
    {
        $content = [
            'email' => 'artem@example.com',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/forgot', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::forgot
     */
    public function testForgot400(): void
    {
        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/forgot', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::reset
     */
    public function testReset200(): void
    {
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('artem@example.com');

        $content = [
            'token'    => $user->generateResetToken(new \DateInterval('PT2H')),
            'password' => 'secret',
        ];

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        $this->client->jsonRequest(Request::METHOD_POST, '/api/reset', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::reset
     */
    public function testReset400(): void
    {
        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/reset', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::reset
     */
    public function testReset404(): void
    {
        $content = [
            'token'    => 'dbdc18aec6e3405ea4c770441fdb02ae',
            'password' => 'secret',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/reset', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
