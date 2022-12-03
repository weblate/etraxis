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

use App\Entity\Issue;
use App\Message\AbstractCollectionQuery;
use App\Message\Issues as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use App\Security\Voter\IssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * API controller for 'Issue' resource.
 */
#[Route('/api/issues')]
#[IsGranted('ROLE_USER')]
#[API\Tag('Issues')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class IssuesController extends AbstractController implements ApiControllerInterface
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
     * Returns list of issues.
     */
    #[Route('', name: 'api_issues_list', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_OFFSET, in: self::PARAMETER_QUERY, description: 'Zero-based index of the first item to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, default: 0))]
    #[API\Parameter(name: self::QUERY_LIMIT, in: self::PARAMETER_QUERY, description: 'Maximum number of items to return.', schema: new API\Schema(type: self::TYPE_INTEGER, minimum: 0, maximum: 100, default: 100))]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetIssuesQuery::ISSUE_ID, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_SUBJECT, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CLONED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CRITICAL, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_SUSPENDED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CLOSED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AGE, type: self::TYPE_INTEGER),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetIssuesQuery::ISSUE_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_SUBJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CREATED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CHANGED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CLOSED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AGE, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: self::COLLECTION_TOTAL, type: self::TYPE_INTEGER, description: 'Total number of all available items.'),
            new API\Property(property: self::COLLECTION_ITEMS, type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Issue::class, groups: ['info']))),
        ]
    ))]
    public function listIssues(Message\GetIssuesQuery $query, NormalizerInterface $normalizer): JsonResponse
    {
        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Creates new issue.
     */
    #[Route('', name: 'api_issues_create', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\CreateIssueCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Issue::class, groups: ['info']), headers: [
        new API\Header(header: 'Location', description: 'URI for the created issue.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function createIssue(Message\CreateIssueCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $issue = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($issue, 'json', [AbstractNormalizer::GROUPS => 'info']);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Downloads list of issues as a CSV-file.
     */
    #[Route('/csv', name: 'api_issues_download_csv', methods: [Request::METHOD_GET])]
    #[API\Parameter(name: self::QUERY_SEARCH, in: self::PARAMETER_QUERY, description: 'Optional search value.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Parameter(name: self::QUERY_FILTERS, in: self::PARAMETER_QUERY, description: 'Optional filters.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetIssuesQuery::ISSUE_ID, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_SUBJECT, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE, type: self::TYPE_INTEGER),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE_NAME, type: self::TYPE_STRING),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CLONED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CRITICAL, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_SUSPENDED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_IS_CLOSED, type: self::TYPE_BOOLEAN),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AGE, type: self::TYPE_INTEGER),
        ]
    ))]
    #[API\Parameter(name: self::QUERY_ORDER, in: self::PARAMETER_QUERY, description: 'Optional sorting.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: Message\GetIssuesQuery::ISSUE_ID, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_SUBJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_PROJECT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_TEMPLATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_STATE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AUTHOR, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_RESPONSIBLE, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CREATED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CHANGED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_CLOSED_AT, default: AbstractCollectionQuery::SORT_ASC),
            new API\Property(property: Message\GetIssuesQuery::ISSUE_AGE, default: AbstractCollectionQuery::SORT_ASC),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    public function downloadIssuesAsCsv(Message\GetIssuesQuery $query, SerializerInterface $serializer): Response
    {
        $query->clearLimit();
        $collection = $this->queryBus->execute($query);

        $content = $serializer->serialize($collection->getItems(), CsvEncoder::FORMAT, [AbstractNormalizer::GROUPS => 'info']);

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'eTraxis.csv');
        $response->headers->set('Content-Disposition', $disposition);
        $response->setPrivate();

        return $response;
    }

    /**
     * Marks specified issues as read.
     */
    #[Route('/read', name: 'api_issues_read_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\MarkAsReadCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    public function readIssues(Message\MarkAsReadCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Marks specified issues as unread.
     */
    #[Route('/unread', name: 'api_issues_unread_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\MarkAsUnreadCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    public function unreadIssues(Message\MarkAsUnreadCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns specified issue.
     */
    #[Route('/{id}', name: 'api_issues_get', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: Issue::class, groups: ['info']))]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getIssue(Issue $issue, NormalizerInterface $normalizer): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        return $this->json($normalizer->normalize($issue, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Clones specified issue.
     */
    #[Route('/{id}', name: 'api_issues_clone', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\CloneIssueCommand::class, groups: ['api']))]
    #[API\Response(response: 201, description: 'Success.', content: new Model(type: Issue::class, groups: ['info']), headers: [
        new API\Header(header: 'Location', description: 'URI for the cloned issue.', schema: new API\Schema(type: self::TYPE_STRING)),
    ])]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function cloneIssue(Message\CloneIssueCommand $command, NormalizerInterface $normalizer): JsonResponse
    {
        $issue = $this->commandBus->handleWithResult($command);

        $json = $normalizer->normalize($issue, 'json', [AbstractNormalizer::GROUPS => 'info']);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($json, Response::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Updates specified issue.
     */
    #[Route('/{id}', name: 'api_issues_update', methods: [Request::METHOD_PUT], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\UpdateIssueCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function updateIssue(Message\UpdateIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified issue.
     */
    #[Route('/{id}', name: 'api_issues_delete', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function deleteIssue(Message\DeleteIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Changes state of specified issue.
     */
    #[Route('/{id}/state/{state}', name: 'api_issues_state', methods: [Request::METHOD_POST], requirements: ['id' => '\d+', 'state' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'state', in: self::PARAMETER_PATH, description: 'State ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\ChangeStateCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function changeState(Message\ChangeStateCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Changes current assignee of specified issue.
     */
    #[Route('/{id}/assign/{user}', name: 'api_issues_assign', methods: [Request::METHOD_POST], requirements: ['id' => '\d+', 'user' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'user', in: self::PARAMETER_PATH, description: 'User ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function assignIssue(Message\ReassignIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Suspends specified issue.
     */
    #[Route('/{id}/suspend', name: 'api_issues_suspend', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\SuspendIssueCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function suspendIssue(Message\SuspendIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resumes specified issue.
     */
    #[Route('/{id}/resume', name: 'api_issues_resume', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function resumeIssue(Message\ResumeIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
