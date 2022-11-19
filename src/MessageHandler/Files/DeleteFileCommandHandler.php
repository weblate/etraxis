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
use App\Message\Files\DeleteFileCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\FileVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DeleteFileCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly FileRepositoryInterface $fileRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(DeleteFileCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\File $file */
        $file = $this->fileRepository->find($command->getFile());

        if (!$file || $file->isRemoved()) {
            throw new NotFoundHttpException('Unknown file.');
        }

        $issue = $file->getEvent()->getIssue();

        if (!$this->security->isGranted(FileVoter::DELETE_FILE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this file.');
        }

        $event = new Event($issue, $user, EventTypeEnum::FileDeleted, $file->getFileName());
        $issue->getEvents()->add($event);

        $file->remove();

        $this->issueRepository->persist($issue);
        $this->fileRepository->persist($file);

        $filename = $this->fileRepository->getFullPath($file);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
