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

namespace App\MessageHandler\Files;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\File;
use App\Message\Files\AttachFileCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\FileVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class AttachFileCommandHandler implements CommandHandlerInterface
{
    private const MEGABYTE = 1048576;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly int $maxsize
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(AttachFileCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(FileVoter::ATTACH_FILE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to attach a file to this issue.');
        }

        if ($command->getFile()->getSize() > $this->maxsize * self::MEGABYTE) {
            throw new BadRequestHttpException(sprintf('The file size must not exceed %d MB.', $this->maxsize));
        }

        $event = new Event($issue, $user, EventTypeEnum::FileAttached, $command->getFile()->getClientOriginalName());
        $issue->getEvents()->add($event);

        $file = new File(
            $event,
            $command->getFile()->getClientOriginalName(),
            $command->getFile()->getSize(),
            $command->getFile()->getClientMimeType() ?? File::DEFAULT_MIMETYPE
        );

        $directory = dirname($this->fileRepository->getFullPath($file));
        $command->getFile()->move($directory, $file->getUid());

        $this->issueRepository->persist($issue);
        $this->fileRepository->persist($file);
    }
}
