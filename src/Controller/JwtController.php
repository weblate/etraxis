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

use App\Message\Security\GenerateJwtCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for JWT authentication.
 */
#[Route('/api')]
#[API\Tag('Authentication')]
class JwtController extends AbstractController implements ApiControllerInterface
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
    #[API\RequestBody(content: new Model(type: GenerateJwtCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'token', type: self::TYPE_STRING, description: 'JSON web token.'),
        ]
    ))]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Invalid credentials.')]
    public function login(GenerateJwtCommand $command): JsonResponse
    {
        $token = $this->commandBus->handleWithResult($command);

        return $this->json(['token' => $token]);
    }
}
