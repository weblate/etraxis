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

use App\Entity\Change;
use App\Entity\Comment;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\File;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\Transition;
use App\Entity\User;
use App\Message\AbstractCollectionQuery;
use App\Message\Issues as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use App\Repository\Contracts\ChangeRepositoryInterface;
use App\Repository\Contracts\CommentRepositoryInterface;
use App\Repository\Contracts\DecimalValueRepositoryInterface;
use App\Repository\Contracts\DependencyRepositoryInterface;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Repository\Contracts\RelatedIssueRepositoryInterface;
use App\Repository\Contracts\StringValueRepositoryInterface;
use App\Repository\Contracts\TextValueRepositoryInterface;
use App\Repository\Contracts\WatcherRepositoryInterface;
use App\Security\Voter\CommentVoter;
use App\Security\Voter\DependencyVoter;
use App\Security\Voter\FileVoter;
use App\Security\Voter\IssueVoter;
use App\Security\Voter\RelatedIssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'issue', ref: new Model(type: Issue::class, groups: ['info'])),
            new API\Property(property: 'events', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Event::class, groups: ['info'])), description: 'List of events (ordered).'),
            new API\Property(property: 'transitions', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Transition::class, groups: ['info'])), description: 'List of transitions (ordered).'),
            new API\Property(property: 'states', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: State::class, groups: ['info'])), description: 'List of states the issue can be moved to (ordered).'),
            new API\Property(property: 'assignees', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: User::class, groups: ['info'])), description: 'List of possible assignees (ordered).'),
            new API\Property(property: 'values', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: FieldValue::class, groups: ['info'])), description: 'List of current values for all editable fields (ordered).'),
            new API\Property(property: 'changes', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Change::class, groups: ['info'])), description: 'List of field changes (ordered).'),
            new API\Property(property: 'watchers', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: User::class, groups: ['info'])), description: 'List of watchers (unordered).'),
            new API\Property(property: 'comments', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Comment::class, groups: ['info'])), description: 'List of comments (ordered).'),
            new API\Property(property: 'files', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: File::class, groups: ['info'])), description: 'List of files (ordered).'),
            new API\Property(property: 'dependencies', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Issue::class, groups: ['info'])), description: 'List of dependencies (ordered).'),
            new API\Property(property: 'related', type: self::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Issue::class, groups: ['info'])), description: 'List of related issues (ordered).'),
            new API\Property(property: 'actions', type: self::TYPE_OBJECT, properties: [
                new API\Property(property: IssueVoter::UPDATE_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: IssueVoter::DELETE_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: IssueVoter::CHANGE_STATE, type: self::TYPE_BOOLEAN),
                new API\Property(property: IssueVoter::REASSIGN_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: IssueVoter::SUSPEND_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: IssueVoter::RESUME_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: CommentVoter::ADD_PUBLIC_COMMENT, type: self::TYPE_BOOLEAN),
                new API\Property(property: CommentVoter::ADD_PRIVATE_COMMENT, type: self::TYPE_BOOLEAN),
                new API\Property(property: FileVoter::ATTACH_FILE, type: self::TYPE_BOOLEAN),
                new API\Property(property: FileVoter::DELETE_FILE, type: self::TYPE_BOOLEAN),
                new API\Property(property: DependencyVoter::ADD_DEPENDENCY, type: self::TYPE_BOOLEAN),
                new API\Property(property: DependencyVoter::REMOVE_DEPENDENCY, type: self::TYPE_BOOLEAN),
                new API\Property(property: RelatedIssueVoter::ADD_RELATED_ISSUE, type: self::TYPE_BOOLEAN),
                new API\Property(property: RelatedIssueVoter::REMOVE_RELATED_ISSUE, type: self::TYPE_BOOLEAN),
            ], description: 'List of actions on the issue currently available to the user.'),
        ]
    ))]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getIssue(
        Issue $issue,
        NormalizerInterface $normalizer,
        IssueRepositoryInterface $issueRepository,
        FieldValueRepositoryInterface $fieldValueRepository,
        DecimalValueRepositoryInterface $decimalValueRepository,
        StringValueRepositoryInterface $stringValueRepository,
        TextValueRepositoryInterface $textValueRepository,
        ListItemRepositoryInterface $listItemRepository,
        ChangeRepositoryInterface $changeRepository,
        WatcherRepositoryInterface $watcherRepository,
        CommentRepositoryInterface $commentRepository,
        FileRepositoryInterface $fileRepository,
        DependencyRepositoryInterface $dependencyRepository,
        RelatedIssueRepositoryInterface $relatedIssueRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        /** @var User $user */
        $user = $this->getUser();

        // Get extra data.
        $states        = $issueRepository->getTransitionsByUser($issue, $user);
        $assignees     = $issueRepository->getResponsiblesByState($issue->getState());
        $values        = $fieldValueRepository->getLatestValues($issue, $user);
        $changes       = $changeRepository->findAllByIssue($issue, $user);
        $watchers      = $watcherRepository->findAllByIssue($issue);
        $comments      = $commentRepository->findAllByIssue($issue, !$this->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issue));
        $files         = $fileRepository->findAllByIssue($issue);
        $dependencies  = $dependencyRepository->findAllByIssue($issue);
        $relatedIssues = $relatedIssueRepository->findAllByIssue($issue);

        $allValues   = $fieldValueRepository->findAllByIssue($issue, $user);
        $transitions = array_values(array_unique(
            array_map(fn (FieldValue $value) => $value->getTransition(), $allValues)
        ));

        // Get IDs of all field values which are foreign keys to other entities.
        $getIds = function (array $values, array $changes, FieldTypeEnum $type): array {
            $values  = array_filter($values, fn (FieldValue $value) => $value->getField()->getType() === $type);
            $changes = array_filter($changes, fn (Change $change) => (
                null === $change->getField() && FieldTypeEnum::String === $type || $change->getField()?->getType() === $type
            ));

            $ids = array_merge(
                array_map(fn (FieldValue $value) => $value->getValue(), $values),
                array_map(fn (Change $change) => $change->getOldValue(), $changes),
                array_map(fn (Change $change) => $change->getNewValue(), $changes),
            );

            return array_unique(array_filter($ids, fn (?int $id) => null !== $id));
        };

        // Warmup repositories with field values.
        $decimalValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Decimal));
        $issueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Issue));
        $listItemRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::List));
        $stringValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::String));
        $textValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Text));

        // Figure out the list of actions on the issue, which are currently available to the user.
        $actions = [
            IssueVoter::UPDATE_ISSUE,
            IssueVoter::DELETE_ISSUE,
            IssueVoter::CHANGE_STATE,
            IssueVoter::REASSIGN_ISSUE,
            IssueVoter::SUSPEND_ISSUE,
            IssueVoter::RESUME_ISSUE,
            CommentVoter::ADD_PUBLIC_COMMENT,
            CommentVoter::ADD_PRIVATE_COMMENT,
            FileVoter::ATTACH_FILE,
            FileVoter::DELETE_FILE,
            DependencyVoter::ADD_DEPENDENCY,
            DependencyVoter::REMOVE_DEPENDENCY,
            RelatedIssueVoter::ADD_RELATED_ISSUE,
            RelatedIssueVoter::REMOVE_RELATED_ISSUE,
        ];

        $actions = array_filter($actions, fn (string $attribute) => $this->isGranted($attribute, $issue));

        return $this->json([
            'issue'        => $normalizer->normalize($issue, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'events'       => $normalizer->normalize($issue->getEvents(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'transitions'  => $normalizer->normalize($transitions, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'states'       => $normalizer->normalize($states, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'assignees'    => $normalizer->normalize($assignees, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'values'       => $normalizer->normalize($values, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'changes'      => $normalizer->normalize($changes, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'watchers'     => $normalizer->normalize($watchers, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'comments'     => $normalizer->normalize($comments, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'files'        => $normalizer->normalize($files, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'dependencies' => $normalizer->normalize($dependencies, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'related'      => $normalizer->normalize($relatedIssues, 'json', [AbstractNormalizer::GROUPS => 'info']),
            'actions'      => array_fill_keys($actions, true),
        ]);
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
