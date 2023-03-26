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

namespace App\Security\Authenticator;

use App\Entity\User;
use App\Utils\SecondsEnum;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Authenticator\JwtAuthenticator
 */
final class JwtAuthenticatorTest extends WebTestCase
{
    private const SECRET  = '$ecretf0rt3st';
    private const JWT_ALG = 'HS256';

    private JwtAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $serializer  = self::getContainer()->get('serializer');
        $rateLimiter = self::getContainer()->get('limiter.authenticated_api');

        $this->authenticator = new JwtAuthenticator($serializer, $rateLimiter);
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccess(): void
    {
        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        self::assertTrue($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsNotApiEndpoint(): void
    {
        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/admin');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsNoHeader(): void
    {
        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsWrongHeader(): void
    {
        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => $token]);

        self::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateSuccess(): void
    {
        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        $passport = $this->authenticator->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertTrue($passport->hasBadge(UserBadge::class));

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('artem@example.com', $badge->getUserIdentifier());
    }

    /**
     * @covers ::authenticate
     */
    public function testInvalidSignature(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('JWT signature is invalid.');

        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $token .= 'x';

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        $passport = $this->authenticator->authenticate($request);
        self::assertFalse($passport->hasBadge(UserBadge::class));
    }

    /**
     * @covers ::authenticate
     */
    public function testExpiredToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('JWT is expired.');

        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() - SecondsEnum::OneMinute->value,
            'iat' => time() - SecondsEnum::TwoHours->value,
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        $passport = $this->authenticator->authenticate($request);
        self::assertFalse($passport->hasBadge(UserBadge::class));
    }

    /**
     * @covers ::authenticate
     */
    public function testMissingSubject(): void
    {
        $token = JWT::encode([
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        $passport = $this->authenticator->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertTrue($passport->hasBadge(UserBadge::class));

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('', $badge->getUserIdentifier());
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess(): void
    {
        $token = JWT::encode([
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

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

        $token = JWT::encode([
            'sub' => 'unknown@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ], self::SECRET, self::JWT_ALG);

        $request = new Request();

        $request->server->set('REQUEST_URI', '/api/my/profile');
        $request->headers->add(['Authorization' => 'Bearer '.$token]);

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertNull($response);
    }
}
