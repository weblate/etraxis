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

use App\Security\LDAP\LdapCredentialsChecker;
use App\Security\LDAP\LdapUserLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Authenticates users using LDAP server.
 */
class LdapAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly LdapUserLoader $ldapUserLoader,
        protected readonly LdapCredentialsChecker $ldapCredentialsChecker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): ?bool
    {
        $endpoints = [
            $this->urlGenerator->generate('login'),
            $this->urlGenerator->generate('api_login'),
        ];

        return 'json' === $request->getContentTypeFormat()
            && $request->isMethod('POST')
            && in_array($request->getPathInfo(), $endpoints, true);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request): Passport
    {
        $content = json_decode($request->getContent());

        $email    = $content->email    ?? false;
        $password = $content->password ?? false;

        if (!$email || !$password) {
            throw new AuthenticationException();
        }

        $badges = [];

        if ($request->getPathInfo() === $this->urlGenerator->generate('login')) {
            $badges[] = new CsrfTokenBadge('authenticate', $content->csrf ?? null);
            $badges[] = new RememberMeBadge();

            $request->request->set('_remember_me', $content->remember ?? false);
        }

        return new Passport(
            new UserBadge($email, $this->ldapUserLoader),
            new CustomCredentials($this->ldapCredentialsChecker, $password),
            $badges
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
