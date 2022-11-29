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
use App\Entity\State;
use App\Entity\StateGroupTransition;
use App\Entity\StateResponsibleGroup;
use App\Entity\StateRoleTransition;
use App\Message\AbstractCollectionQuery;
use App\Message\States as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API controller for 'State' resource.
 */
#[Route('/api/states')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('States')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class StatesController extends AbstractController implements ApiControllerInterface
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
     * Returns list of states.
     */
    #[Route('', name: 'api_states_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetStatesQuery::STATE_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetStatesQuery::STATE_TEMPLATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetStatesQuery::STATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetStatesQuery::STATE_TYPE, type: self::TYPE_STRING),
            new API\Property(property: Message\GetStatesQuery::STATE_RESPONSIBLE, type: self::TYPE_STRING),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetStatesQuery::STATE_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetStatesQuery::STATE_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetStatesQuery::STATE_TEMPLATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetStatesQuery::STATE_NAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetStatesQuery::STATE_TYPE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetStatesQuery::STATE_RESPONSIBLE, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: State::class, groups: ['api']))),
        ]
    ))]
    public function listStates(Message\GetStatesQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new state.
     */
    #[Route('', name: 'api_states_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateStateCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: State::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created state.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createState(Message\CreateStateCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $state = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($state, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_states_get', [
            'id' => $state->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified state.
     */
    #[Route('/{id}', name: 'api_states_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: State::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getState(State $state, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($state, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified state.
     */
    #[Route('/{id}', name: 'api_states_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateStateCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateState(Message\UpdateStateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified state.
     */
    #[Route('/{id}', name: 'api_states_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteState(Message\DeleteStateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Sets specified state as initial.
     */
    #[Route('/{id}/initial', name: 'api_states_initial', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setInitialState(Message\SetInitialStateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns transitions available for specified state.
     */
    #[Route('/{id}/transitions', name: 'api_states_get_transitions', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'roles', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: StateRoleTransition::class, groups: ['api']))),
            new API\Property(property: 'groups', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: StateGroupTransition::class, groups: ['api']))),
        ]
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getTransitions(State $state, NormalizerInterface $normalizer): JsonResponse
    {
        $json = [
            'roles'  => $state->getRoleTransitions(),
            'groups' => $state->getGroupTransitions(),
        ];

        return $this->json($normalizer->normalize($json, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Sets transitions available for specified state.
     */
    #[Route('/{id}/transitions', name: 'api_states_set_transitions', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'toState', type: self::TYPE_INTEGER, description: 'Destination state.'),
            new API\Property(property: 'roles', type: self::TYPE_ARRAY, description: 'List of system roles.', items: new API\Items(type: self::TYPE_STRING)),
            new API\Property(property: 'groups', type: self::TYPE_ARRAY, description: 'List of group IDs.', items: new API\Items(type: self::TYPE_INTEGER)),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setTransitions(Message\SetRolesTransitionCommand $rolesCommand, Message\SetGroupsTransitionCommand $groupsCommand): JsonResponse
    {
        $this->commandBus->handle($rolesCommand);
        $this->commandBus->handle($groupsCommand);

        return $this->json(null);
    }

    /**
     * Returns responsible groups for specified state.
     */
    #[Route('/{id}/responsibles', name: 'api_states_get_responsibles', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: Group::class, groups: ['api']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getResponsibleGroups(State $state, NormalizerInterface $normalizer): JsonResponse
    {
        $groups = $state->getResponsibleGroups()->map(fn (StateResponsibleGroup $group) => $group->getGroup());

        return $this->json($normalizer->normalize($groups, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Sets responsible groups for specified state.
     */
    #[Route('/{id}/responsibles', name: 'api_states_set_responsibles', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\SetResponsibleGroupsCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setResponsibleGroups(Message\SetResponsibleGroupsCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
