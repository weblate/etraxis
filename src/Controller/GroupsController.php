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
use App\Message\Groups as Message;
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
 * API controller for 'Group' resource.
 */
#[Route('/api/groups')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('Groups')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class GroupsController extends AbstractController implements ApiControllerInterface
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
     * Returns list of groups.
     */
    #[Route('', name: 'api_groups_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetGroupsQuery::GROUP_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetGroupsQuery::GROUP_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetGroupsQuery::GROUP_DESCRIPTION, type: self::TYPE_STRING),
            new API\Property(property: Message\GetGroupsQuery::GROUP_IS_GLOBAL, type: self::TYPE_BOOLEAN),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetGroupsQuery::GROUP_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetGroupsQuery::GROUP_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetGroupsQuery::GROUP_NAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetGroupsQuery::GROUP_DESCRIPTION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetGroupsQuery::GROUP_IS_GLOBAL, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Group::class, groups: ['api']))),
        ]
    ))]
    public function listGroups(Message\GetGroupsQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new group.
     */
    #[Route('', name: 'api_groups_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateGroupCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Group::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created group.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createGroup(Message\CreateGroupCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $group = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($group, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_groups_get', [
            'id' => $group->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified group.
     */
    #[Route('/{id}', name: 'api_groups_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Group ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: Group::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getGroup(Group $group, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($group, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified group.
     */
    #[Route('/{id}', name: 'api_groups_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Group ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateGroupCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateGroup(Message\UpdateGroupCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified group.
     */
    #[Route('/{id}', name: 'api_groups_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Group ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteGroup(Message\DeleteGroupCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of members for specified group.
     */
    #[Route('/{id}/members', name: 'api_groups_get_members', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Group ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: User::class, groups: ['api']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getMembers(Group $group, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($group->getMembers(), 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Sets members for specified group.
     */
    #[Route('/{id}/members', name: 'api_groups_set_members', methods: [Request::METHOD_PATCH], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Group ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'add', type: self::TYPE_ARRAY, description: 'List of user IDs to add.', items: new API\Items(type: self::TYPE_INTEGER)),
            new API\Property(property: 'remove', type: self::TYPE_ARRAY, description: 'List of user IDs to remove.', items: new API\Items(type: self::TYPE_INTEGER)),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setMembers(Request $request, int $id, EntityManagerInterface $manager): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        $add    = is_array($content['add']    ?? null) ? $content['add'] : [];
        $remove = is_array($content['remove'] ?? null) ? $content['remove'] : [];

        $manager->beginTransaction();

        $command = new Message\AddMembersCommand($id, array_diff($add, $remove));

        if (count($command->getUsers())) {
            $this->commandBus->handle($command);
        }

        $command = new Message\RemoveMembersCommand($id, array_diff($remove, $add));

        if (count($command->getUsers())) {
            $this->commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }
}
