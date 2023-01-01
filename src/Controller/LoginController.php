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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Login controller.
 */
class LoginController extends AbstractController
{
    use TargetPathTrait;

    /**
     * Login page.
     */
    #[Route('/login', name: 'login', methods: [Request::METHOD_GET])]
    #[Route('/forgot', methods: [Request::METHOD_GET])]
    #[Route('/reset/{token}', name: 'reset_password', methods: [Request::METHOD_GET])]
    public function index(Request $request, Security $security): Response
    {
        if ($this->getUser()) {
            $firewallName = $security->getFirewallConfig($request)?->getName();
            $targetPath   = $firewallName ? $this->getTargetPath($request->getSession(), $firewallName) : null;

            return new RedirectResponse($targetPath ?? $this->generateUrl('homepage'));
        }

        return $this->render('login/index.html.twig');
    }

    /**
     * Authenticates user using submitted credentials.
     */
    #[Route('/login', methods: [Request::METHOD_POST])]
    public function authenticate(AuthenticationUtils $utils, TranslatorInterface $translator): JsonResponse
    {
        if (!$this->getUser()) {
            $message = $translator->trans(
                $utils->getLastAuthenticationError()?->getMessageKey() ?? 'Invalid credentials.',
                $utils->getLastAuthenticationError()?->getMessageData() ?? [],
                'security'
            );

            return $this->json($message, Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(null);
    }
}
