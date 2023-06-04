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

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentication entry point.
 */
class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @see AuthenticationEntryPointInterface::start
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $request->isXmlHttpRequest() || 'json' === $request->getPreferredFormat()
            ? new JsonResponse($authException?->getMessage(), Response::HTTP_UNAUTHORIZED)
            : new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
