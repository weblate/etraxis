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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
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
                ['login',     [], UrlGeneratorInterface::ABSOLUTE_PATH, '/login'],
                ['api_login', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/api/login'],
            ])
        ;

        $this->authenticator = new PasswordAuthenticator($urlGenerator);
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccessWeb(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setMethod(Request::METHOD_POST);
        $request->server->set('REQUEST_URI', '/login');
        $request->headers->set('Content-Type', 'application/json');

        self::assertTrue($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccessApi(): void
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
    public function testAuthenticateWeb(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->server->set('REQUEST_URI', '/login');

        $passport = $this->authenticator->authenticate($request);

        self::assertTrue($passport->hasBadge(UserBadge::class));
        self::assertTrue($passport->hasBadge(PasswordCredentials::class));

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('admin@example.com', $badge->getUserIdentifier());

        /** @var PasswordCredentials $badge */
        $badge = $passport->getBadge(PasswordCredentials::class);
        self::assertSame('secret', $badge->getPassword());

        self::assertTrue($passport->hasBadge(CsrfTokenBadge::class));
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateApi(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->server->set('REQUEST_URI', '/api/login');

        $passport = $this->authenticator->authenticate($request);

        self::assertTrue($passport->hasBadge(UserBadge::class));
        self::assertTrue($passport->hasBadge(PasswordCredentials::class));

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('admin@example.com', $badge->getUserIdentifier());

        /** @var PasswordCredentials $badge */
        $badge = $passport->getBadge(PasswordCredentials::class);
        self::assertSame('secret', $badge->getPassword());

        self::assertFalse($passport->hasBadge(CsrfTokenBadge::class));
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

        $request->server->set('REQUEST_URI', '/login');

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
        $exception = new AuthenticationException('Bad credentials.');

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects(self::exactly(1))
            ->method('set')
            ->withConsecutive(
                [Security::AUTHENTICATION_ERROR, $exception]
            )
        ;

        $request = new Request(content: json_encode([
            'email'    => 'admin@example.com',
            'password' => 'secret',
        ]));

        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertNull($response);
    }
}
