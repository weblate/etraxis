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

use App\Entity\File;
use App\Entity\Issue;
use App\Message\Files as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API controller for 'File' resource.
 */
#[Route('/api/issues')]
#[IsGranted('ROLE_USER')]
#[API\Tag('Files')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 403, description: 'Access denied.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class FilesController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * Returns list of files.
     */
    #[Route('/{id}/files', name: 'api_files_list', methods: [Request::METHOD_GET], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: File::class, groups: ['info']))
    ))]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function listFiles(Issue $issue, NormalizerInterface $normalizer, FileRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue, 'You are not allowed to view this issue.');

        $files = $repository->findAllByIssue($issue);

        return $this->json($normalizer->normalize($files, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * Attaches new file.
     */
    #[Route('/{id}/files', name: 'api_files_add', methods: [Request::METHOD_POST], requirements: ['id' => '\d+'])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\RequestBody(content: new Model(type: Message\AttachFileCommand::class, groups: ['api']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function attachFile(Message\AttachFileCommand $command): JsonResponse
    {
        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Downloads specified file.
     */
    #[Route('/{id}/files/{uid}', name: 'api_files_download', methods: [Request::METHOD_GET], requirements: [
        'id'  => '\d+',
        'uid' => '^([A-Za-z0-9]{8}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{12})$',
    ])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'uid', in: self::PARAMETER_PATH, description: 'File UID.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 404, description: 'Resource not found.')]
    public function downloadFile(int $id, string $uid, FileRepositoryInterface $repository): BinaryFileResponse
    {
        $file = $repository->findOneByUid($uid);

        if (!$file || $file->getEvent()->getIssue()->getId() !== $id) {
            throw $this->createNotFoundException('Unknown file.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $file->getEvent()->getIssue(), 'You are not allowed to view this issue.');

        $path = $repository->getFullPath($file);

        if (!file_exists($path)) {
            throw $this->createNotFoundException('Unknown file.');
        }

        $response = new BinaryFileResponse($path);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getFileName());
        $response->setPrivate();

        return $response;
    }

    /**
     * Deletes specified file.
     */
    #[Route('/{id}/files/{uid}', name: 'api_files_delete', methods: [Request::METHOD_DELETE], requirements: [
        'id'  => '\d+',
        'uid' => '^([A-Za-z0-9]{8}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{12})$',
    ])]
    #[API\Parameter(name: 'id', in: self::PARAMETER_PATH, description: 'Issue ID.', schema: new API\Schema(type: self::TYPE_INTEGER))]
    #[API\Parameter(name: 'uid', in: self::PARAMETER_PATH, description: 'File UID.', schema: new API\Schema(type: self::TYPE_STRING))]
    #[API\Response(response: 200, description: 'Success.')]
    public function deleteFile(int $id, string $uid, FileRepositoryInterface $repository): JsonResponse
    {
        $file = $repository->findOneByUid($uid);

        if ($file && $file->getEvent()->getIssue()->getId() === $id) {
            $command = new Message\DeleteFileCommand($file->getId());

            $this->commandBus->handle($command);
        }

        return $this->json(null);
    }
}
