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

use App\Entity\Comment;
use App\Entity\Issue;
use App\Message\Comments as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\CommentRepositoryInterface;
use App\Security\Voter\CommentVoter;
use App\Security\Voter\IssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API controller for 'Comment' resource.
 */
#[Route('/api/issues')]
#[IsGranted('ROLE_USER')]
#[API\Tag('Comments')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class CommentsController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * Returns list of comments (ordered by time).
     */
    #[Route('/{id}/comments', name: 'api_comments_list', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: Comment::class, groups: ['info']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function listComments(Issue $issue, NormalizerInterface $normalizer, CommentRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        $comments = $repository->findAllByIssue($issue, !$this->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $issue));

        return $this->json($normalizer->normalize($comments, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Adds new comment.
     */
    #[Route('/{id}/comments', name: 'api_comments_add', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\AddCommentCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function addComment(Message\AddCommentCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
