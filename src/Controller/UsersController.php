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

use App\Entity\Group;
use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Users as Message;
use App\Message\UserSettings\SetPasswordCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API controller for 'User' resource.
 */
#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('Users')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class UsersController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly CommandBusInterface $commandBus,
        protected readonly QueryBusInterface $queryBus
    ) {
    }

    /**
     * Returns list of users.
     */
    #[Route('', name: 'api_users_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetUsersQuery::USER_EMAIL, type: self::TYPE_STRING),
            new API\Property(property: Message\GetUsersQuery::USER_FULLNAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetUsersQuery::USER_DESCRIPTION, type: self::TYPE_STRING),
            new API\Property(property: Message\GetUsersQuery::USER_IS_ADMIN, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetUsersQuery::USER_IS_DISABLED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetUsersQuery::USER_PROVIDER, type: self::TYPE_STRING),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetUsersQuery::USER_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetUsersQuery::USER_EMAIL, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetUsersQuery::USER_FULLNAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetUsersQuery::USER_DESCRIPTION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetUsersQuery::USER_IS_ADMIN, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetUsersQuery::USER_PROVIDER, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: User::class, groups: ['api']))),
        ]
    ))]
    public function listUsers(Message\GetUsersQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new user.
     */
    #[Route('', name: 'api_users_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateUserCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: User::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created user.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createUser(Message\CreateUserCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $user = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($user, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_users_get', [
            'id' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Disables specified users.
     */
    #[Route('/disable', name: 'api_users_disable_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\DisableUsersCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function disableMultipleUsers(Message\DisableUsersCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Enables specified users.
     */
    #[Route('/enable', name: 'api_users_enable_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\EnableUsersCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function enableMultipleUsers(Message\EnableUsersCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns specified user.
     */
    #[Route('/{id}', name: 'api_users_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: User::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function retrieveUser(User $user, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($user, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified user.
     */
    #[Route('/{id}', name: 'api_users_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateUserCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateUser(Message\UpdateUserCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified user.
     */
    #[Route('/{id}', name: 'api_users_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteUser(Message\DeleteUserCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of groups for specified user.
     */
    #[Route('/{id}/groups', name: 'api_users_get_groups', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: Group::class, groups: ['api']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getGroups(User $user, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($user->getGroups(), 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Sets groups for specified user.
     */
    #[Route('/{id}/groups', name: 'api_users_set_groups', methods: [Request::METHOD_PATCH], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'add', type: self::TYPE_ARRAY, description: 'List of group IDs to add.', items: new API\Items(type: self::TYPE_INTEGER)),
            new API\Property(property: 'remove', type: self::TYPE_ARRAY, description: 'List of group IDs to remove.', items: new API\Items(type: self::TYPE_INTEGER)),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setGroups(Request $request, int $id, EntityManagerInterface $manager): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        $add    = is_array($content['add']    ?? null) ? $content['add'] : [];
        $remove = is_array($content['remove'] ?? null) ? $content['remove'] : [];

        $manager->beginTransaction();

        $command = new Message\AddGroupsCommand($id, array_diff($add, $remove));

        if (count($command->getGroups())) {
            $this->commandBus->handle($command);
        }

        $command = new Message\RemoveGroupsCommand($id, array_diff($remove, $add));

        if (count($command->getGroups())) {
            $this->commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }

    /**
     * Sets password for specified user.
     */
    #[Route('/{id}/password', name: 'api_users_password', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: SetPasswordCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setPassword(SetPasswordCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Disables specified user.
     */
    #[Route('/{id}/disable', name: 'api_users_disable', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function disableUser(Message\DisableUserCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Enables specified user.
     */
    #[Route('/{id}/enable', name: 'api_users_enable', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function enableUser(Message\EnableUserCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
