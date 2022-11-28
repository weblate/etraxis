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

use App\Entity\Template;
use App\Entity\TemplateGroupPermission;
use App\Entity\TemplateRolePermission;
use App\Message\AbstractCollectionQuery;
use App\Message\Templates as Message;
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
 * API controller for 'Template' resource.
 */
#[Route('/api/templates')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('Templates')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class TemplatesController extends AbstractController implements ApiControllerInterface
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
     * Returns list of templates.
     */
    #[Route('', name: 'api_templates_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_PREFIX, type: self::TYPE_STRING),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_DESCRIPTION, type: self::TYPE_STRING),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_CRITICAL_AGE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_FROZEN_TIME, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_IS_LOCKED, type: self::TYPE_BOOLEAN),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_NAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_PREFIX, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_DESCRIPTION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_CRITICAL_AGE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_FROZEN_TIME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetTemplatesQuery::TEMPLATE_IS_LOCKED, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Template::class, groups: ['api']))),
        ]
    ))]
    public function listTemplates(Message\GetTemplatesQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new template.
     */
    #[Route('', name: 'api_templates_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateTemplateCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Template::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created template.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createTemplate(Message\CreateTemplateCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $template = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($template, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_templates_get', [
            'id' => $template->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified template.
     */
    #[Route('/{id}', name: 'api_templates_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: Template::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getTemplate(Template $template, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($template, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Clones specified template.
     */
    #[Route('/{id}', name: 'api_templates_clone', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\CloneTemplateCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Template::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created template.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function cloneTemplate(Message\CloneTemplateCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $template = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($template, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_templates_get', [
            'id' => $template->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Updates specified template.
     */
    #[Route('/{id}', name: 'api_templates_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateTemplateCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateTemplate(Message\UpdateTemplateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified template.
     */
    #[Route('/{id}', name: 'api_templates_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteTemplate(Message\DeleteTemplateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Locks specified template.
     */
    #[Route('/{id}/lock', name: 'api_templates_lock', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function lockTemplate(Message\LockTemplateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Unlocks specified template.
     */
    #[Route('/{id}/unlock', name: 'api_templates_unlock', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function unlockTemplate(Message\UnlockTemplateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns permissions for specified template.
     */
    #[Route('/{id}/permissions', name: 'api_templates_get_permissions', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'roles', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: TemplateRolePermission::class, groups: ['api']))),
            new API\Property(property: 'groups', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: TemplateGroupPermission::class, groups: ['api']))),
        ]
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getPermissions(Template $template, NormalizerInterface $normalizer): JsonResponse
    {
        $json = [
            'roles'  => $template->getRolePermissions(),
            'groups' => $template->getGroupPermissions(),
        ];

        return $this->json($normalizer->normalize($json, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Sets permissions for specified template.
     */
    #[Route('/{id}/permissions', name: 'api_templates_set_permissions', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Template ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
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
    public function setPermissions(Message\SetRolesPermissionCommand $rolesCommand, Message\SetGroupsPermissionCommand $groupsCommand/*Request $request, int $id, SerializerInterface $serializer*/): JsonResponse
    {
        $this->commandBus->handle($rolesCommand);
        $this->commandBus->handle($groupsCommand);

        return $this->json(null);
    }
}
