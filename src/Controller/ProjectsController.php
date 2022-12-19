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

use App\Entity\Project;
use App\Message\AbstractCollectionQuery;
use App\Message\Projects as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
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
 * API controller for 'Project' resource.
 */
#[Route('/api/projects')]
#[IsGranted('ROLE_ADMIN')]
#[API\Tag('Projects')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class ProjectsController extends AbstractController implements ApiControllerInterface
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
     * Returns list of projects.
     */
    #[Route('', name: 'api_projects_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetProjectsQuery::PROJECT_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_DESCRIPTION, type: self::TYPE_STRING),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_IS_SUSPENDED, type: self::TYPE_BOOLEAN),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetProjectsQuery::PROJECT_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_NAME, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_DESCRIPTION, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_CREATED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetProjectsQuery::PROJECT_IS_SUSPENDED, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Project::class, groups: ['api']))),
        ]
    ))]
    public function listProjects(Message\GetProjectsQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Creates new project.
     */
    #[Route('', name: 'api_projects_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateProjectCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Project::class, groups: ['api']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created project.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createProject(Message\CreateProjectCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $project = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($project, 'json', [AbstractNormalizer::GROUPS => 'api']);

        $url = $this->generateUrl('api_projects_get', [
            'id' => $project->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified project.
     */
    #[Route('/{id}', name: 'api_projects_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Project ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: Project::class, groups: ['api']))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getProject(Project $project, NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($project, 'json', [AbstractNormalizer::GROUPS => 'api']));
    }

    /**
     * Updates specified project.
     */
    #[Route('/{id}', name: 'api_projects_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Project ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateProjectCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateProject(Message\UpdateProjectCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified project.
     */
    #[Route('/{id}', name: 'api_projects_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Project ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteProject(Message\DeleteProjectCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Suspends specified project.
     */
    #[Route('/{id}/suspend', name: 'api_projects_suspend', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Project ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function suspendProject(Message\SuspendProjectCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resumes specified project.
     */
    #[Route('/{id}/resume', name: 'api_projects_resume', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Project ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function resumeProject(Message\ResumeProjectCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
