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

use App\Entity\Issue;
use App\Entity\User;
use App\Message\Issues as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\WatcherRepositoryInterface;
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
 * API controller for 'Watcher' resource.
 */
#[Route('/api/issues')]
#[IsGranted('ROLE_USER')]
#[API\Tag('Watchers')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class WatchersController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * Starts watching for specified issues.
     */
    #[Route('/watch', name: 'api_issues_watch_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\WatchIssuesCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    public function watchIssues(Message\WatchIssuesCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Stops watching for specified issues.
     */
    #[Route('/unwatch', name: 'api_issues_unwatch_multiple', methods: [Request::METHOD_POST])]
    #[API\RequestBody(content: new Model(type: Message\UnwatchIssuesCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    public function unwatchIssues(Message\UnwatchIssuesCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of watchers for specified issue.
     */
    #[Route('/{id}/watchers', name: 'api_issues_watchers', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: User::class, groups: ['info']))
    ))]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function getWatchers(Issue $issue, NormalizerInterface $normalizer, WatcherRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        $watchers = $repository->findAllByIssue($issue);

        return $this->json($normalizer->normalize($watchers, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Starts watching for specified issue.
     */
    #[Route('/{id}/watch', name: 'api_issues_watch', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function watchIssue(Message\WatchIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Stops watching for specified issue.
     */
    #[Route('/{id}/unwatch', name: 'api_issues_unwatch', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    public function unwatchIssue(Message\UnwatchIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
