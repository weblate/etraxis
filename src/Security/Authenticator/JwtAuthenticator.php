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

use App\Serializer\JwtEncoder;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Authenticates users using JSON Web Token.
 */
class JwtAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly DecoderInterface $decoder,
        protected readonly RateLimiterFactory $authenticatedApiLimiter
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
            && str_starts_with($request->headers->get('Authorization', ''), 'Bearer');
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request): Passport
    {
        $limiter = $this->authenticatedApiLimiter->create($request->getClientIp());
        $limiter->consume()->ensureAccepted();

        $header = $request->headers->get('Authorization', '');
        $token  = trim(str_ireplace('Bearer', '', $header));

        try {
            $payload = $this->decoder->decode($token, JwtEncoder::FORMAT);
        } catch (SignatureInvalidException) {
            throw new AuthenticationException('JWT signature is invalid.');
        } catch (ExpiredException) {
            throw new AuthenticationException('JWT is expired.');
        }

        return new SelfValidatingPassport(
            new UserBadge($payload['sub'] ?? '')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
