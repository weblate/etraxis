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

namespace App\Security\Authenticator;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Authenticator\PasswordAuthenticator
 */
final class PasswordAuthenticatorTest extends TestCase
{
    private PasswordAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnMap([
                ['api_login', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/api/login'],
            ])
        ;

        $this->authenticator = new PasswordAuthenticator($urlGenerator);
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccess(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setMethod(Request::METHOD_POST);
        $request->server->set('REQUEST_URI', '/api/login');
        $request->headers->set('Content-Type', 'application/json');

        self::assertTrue($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsNotJson(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setMethod(Request::METHOD_POST);
        $request->server->set('REQUEST_URI', '/api/login');
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsNotPost(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setMethod(Request::METHOD_GET);
        $request->server->set('REQUEST_URI', '/api/login');
        $request->headers->set('Content-Type', 'application/json');

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsWrongUrl(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setMethod(Request::METHOD_POST);
        $request->server->set('REQUEST_URI', '/logout');
        $request->headers->set('Content-Type', 'application/json');

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticate(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $passport = $this->authenticator->authenticate($request);

        self::assertTrue($passport->hasBadge(UserBadge::class));
        self::assertTrue($passport->hasBadge(PasswordCredentials::class));

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('admin@example.com', $badge->getUserIdentifier());

        /** @var PasswordCredentials $badge */
        $badge = $passport->getBadge(PasswordCredentials::class);
        self::assertSame('secret', $badge->getPassword());
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateException(): void
    {
        $this->expectException(AuthenticationException::class);

        $request = new Request(content: json_encode([
            'email' => 'admin@example.com',
        ]));

        $this->authenticator->authenticate($request);
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $token    = new PostAuthenticationToken(new User(), 'main', [User::ROLE_USER]);
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertNull($response);
    }

    /**
     * @covers ::onAuthenticationFailure
     */
    public function testOnAuthenticationFailure(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $exception = new AuthenticationException('Bad credentials.');
        $response  = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertNull($response);
    }
}
