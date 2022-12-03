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
use App\Message\RelatedIssues as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\RelatedIssueRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API controller for 'Related Issue' resource.
 */
#[Route('/api/issues')]
#[IsGranted('ROLE_USER')]
#[API\Tag('Related Issues')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class RelatedIssuesController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * Returns list of related issues (ordered by time).
     */
    #[Route('/{id}/related', name: 'api_related_list', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: Issue::class, groups: ['info']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function listRelatedIssues(Issue $issue, NormalizerInterface $normalizer, RelatedIssueRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        $relatedIssues = $repository->findAllByIssue($issue);

        return $this->json($normalizer->normalize($relatedIssues, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Adds new related issue.
     */
    #[Route('/{id}/related/{issue}', name: 'api_related_add', methods: [Request::METHOD_POST], requirements: ['id' => '\d+', 'issue' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'issue', in: self::PARAMETER_PATH, description: 'Related Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function addRelatedIssue(Message\AddRelatedIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Removes specified related issue.
     */
    #[Route('/{id}/related/{issue}', name: 'api_related_remove', methods: [Request::METHOD_DELETE], requirements: ['id' => '\d+', 'issue' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'issue', in: self::PARAMETER_PATH, description: 'Related Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function removeRelatedIssue(Message\RemoveRelatedIssueCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }
}
