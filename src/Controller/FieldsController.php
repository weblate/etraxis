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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldGroupPermission;
use App\Entity\FieldRolePermission;
use App\Entity\ListItem;
use App\Message\AbstractCollectionQuery;
use App\Message\Fields as Message;
use App\Message\ListItems\CreateListItemCommand;
use App\Message\ListItems\DeleteListItemCommand;
use App\Message\ListItems\UpdateListItemCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * API controller for 'Field' resource.
 */
#[Route('/api/fields')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('Fields')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class FieldsController extends AbstractController implements ApiControllerInterface
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
     * Returns list of fields.
     */
    #[Route('', name: 'api_fields_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetFieldsQuery::FIELD_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetFieldsQuery::FIELD_TEMPLATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetFieldsQuery::FIELD_STATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetFieldsQuery::FIELD_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetFieldsQuery::FIELD_TYPE, type: self::TYPE_STRING),
            new API\Property(property: Message\GetFieldsQuery::FIELD_DESCRIPTION, type: self::TYPE_STRING),
            new API\Property(property: Message\GetFieldsQuery::FIELD_POSITION, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetFieldsQuery::FIELD_IS_REQUIRED, type: self::TYPE_BOOLEAN),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetFieldsQuery::FIELD_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_TEMPLATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_STATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_NAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_TYPE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_DESCRIPTION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_POSITION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetFieldsQuery::FIELD_IS_REQUIRED, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Field::class, groups: ['api']))),
        ]
    ))]
    public function listFields(Message\GetFieldsQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new field.
     */
    #[Route('', name: 'api_fields_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateFieldCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Field::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created field.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createField(Message\CreateFieldCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $field = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($field, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_fields_get', [
            'id' => $field->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified field.
     */
    #[Route('/{id}', name: 'api_fields_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: Field::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getField(Field $field, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($field, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified field.
     */
    #[Route('/{id}', name: 'api_fields_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateFieldCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateField(Message\UpdateFieldCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified field.
     */
    #[Route('/{id}', name: 'api_fields_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteField(Message\DeleteFieldCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Changes position of specified field.
     */
    #[Route('/{id}/position', name: 'api_fields_position', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\SetFieldPositionCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setFieldPosition(Message\SetFieldPositionCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns permissions for specified field.
     */
    #[Route('/{id}/permissions', name: 'api_fields_get_permissions', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'roles', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: FieldRolePermission::class, groups: ['info']))),
            new API\Property(property: 'groups', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: FieldGroupPermission::class, groups: ['info']))),
        ]
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getPermissions(Field $field, NormalizerInterface $normalizer): JsonResponse
    {
        $json = [
            'roles'  => $field->getRolePermissions(),
            'groups' => $field->getGroupPermissions(),
        ];

        return $this->json($normalizer->normalize($json, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Sets permissions for specified field.
     */
    #[Route('/{id}/permissions', name: 'api_fields_set_permissions', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'permission', type: self::TYPE_STRING, description: 'Specific permission.'),
            new API\Property(property: 'roles', type: self::TYPE_ARRAY, description: 'List of system roles.', items: new API\Items(type: self::TYPE_STRING)),
            new API\Property(property: 'groups', type: self::TYPE_ARRAY, description: 'List of group IDs.', items: new API\Items(type: self::TYPE_INTEGER)),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function setPermissions(Message\SetRolesPermissionCommand $rolesCommand, Message\SetGroupsPermissionCommand $groupsCommand): JsonResponse
    {
        $this->commandBus->handle($rolesCommand);
        $this->commandBus->handle($groupsCommand);

        return $this->json(null);
    }

    /**
     * Returns list items of specified field.
     */
    #[Route('/{id}/listitems', name: 'api_listitems_list', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: ListItem::class, groups: ['api']))
    ))]
    #[API\Response(response: 204, description: 'Specified field is not a list.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getListItems(Field $field, NormalizerInterface $normalizer, ListItemRepositoryInterface $repository): JsonResponse
    {
        if (FieldTypeEnum::List !== $field->getType()) {
            return $this->json(null, Response::HTTP_NO_CONTENT);
        }

        $items = $repository->findAllByField($field);

        return $this->json($normalizer->normalize($items, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new list item.
     */
    #[Route('/{id}/listitems', name: 'api_listitems_create', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: CreateListItemCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: ListItem::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created list item.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createListItem(CreateListItemCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        /** @var ListItem $item */
        $item = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($item, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_listitems_get', [
            'id'    => $item->getField()->getId(),
            'value' => $item->getValue(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified list item.
     */
    #[Route('/{id}/listitems/{value}', name: 'api_listitems_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+', 'value' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'value', in: self::PARAMETER_PATH, description: 'List item value.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: ListItem::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getListItem(Field $field, int $value, NormalizerInterface $normalizer, ListItemRepositoryInterface $repository): JsonResponse
    {
        $item = $repository->findOneByValue($field, $value);

        if (null === $item) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        return $this->json($normalizer->normalize($item, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified list item.
     */
    #[Route('/{id}/listitems/{value}', name: 'api_listitems_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+', 'value' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'value', in: self::PARAMETER_PATH, description: 'List item value.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: UpdateListItemCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateListItem(Request $request, Field $field, int $value, SerializerInterface $serializer, ListItemRepositoryInterface $repository): JsonResponse
    {
        $item = $repository->findOneByValue($field, $value);

        if (null === $item) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        /** @var UpdateListItemCommand $command */
        $command = $serializer->deserialize($request->getContent() ?: '{}', UpdateListItemCommand::class, 'json', [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                UpdateListItemCommand::class => ['item' => $item->getId()],
            ],
        ]);

        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified list item.
     */
    #[Route('/{id}/listitems/{value}', name: 'api_listitems_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+', 'value' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Field ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'value', in: self::PARAMETER_PATH, description: 'List item value.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteListItem(Field $field, int $value, ListItemRepositoryInterface $repository): JsonResponse
    {
        $item = $repository->findOneByValue($field, $value);

        if (null !== $item) {
            $command = new DeleteListItemCommand($item->getId());

            $this->commandBus->handle($command);
        }

        return $this->json(null);
    }
}
