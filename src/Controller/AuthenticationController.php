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
use App\Message\Security as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * API controller for JWT authentication.
 */
#[Route('/api')]
#[API\Tag('Authentication')]
class AuthenticationController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * Authenticates user.
     */
    #[Route('/login', name: 'api_login', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: User::class, groups: ['login']))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'token', type: self::TYPE_STRING, description: 'JSON web token.'),
        ]
    ))]
    #[API\Response(response: 404, description: 'Invalid credentials.')]
    #[API\Response(response: 429, description: 'API rate limit exceeded.')]
    public function login(Request $request, TokenStorageInterface $tokenStorage, RateLimiterFactory $anonymousApiLimiter): JsonResponse
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        $limiter->consume()->ensureAccepted();

        $command = new Message\GenerateJwtCommand();
        $token   = $this->commandBus->handleWithResult($command);

        // Logout user from the browser session.
        // todo: refactor after upgrading to Symfony 6.2
        $tokenStorage->setToken(null);

        return $this->json(['token' => $token]);
    }

    /**
     * Marks password of specified eTraxis account as forgotten.
     */
    #[Route('/forgot', name: 'api_forgot', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\ForgotPasswordCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 429, description: 'API rate limit exceeded.')]
    public function forgot(Request $request, Message\ForgotPasswordCommand $command, RateLimiterFactory $anonymousApiLimiter): JsonResponse
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        $limiter->consume()->ensureAccepted();

        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resets password for specified account.
     */
    #[Route('/reset', name: 'api_reset', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\ResetPasswordCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Invalid credentials.')]
    #[API\Response(response: 429, description: 'API rate limit exceeded.')]
    public function reset(Request $request, Message\ResetPasswordCommand $command, RateLimiterFactory $anonymousApiLimiter): JsonResponse
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        $limiter->consume()->ensureAccepted();

        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
