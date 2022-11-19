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

namespace App\MessageHandler\Comments;

use App\Entity\Comment;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Message\Comments\AddCommentCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\CommentRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\CommentVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class AddCommentCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly CommentRepositoryInterface $commentRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(AddCommentCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if ($command->isPrivate()) {
            if (!$this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue privately.');
            }
        } else {
            if (!$this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue.');
            }
        }

        $event = new Event(
            $issue,
            $user,
            $command->isPrivate() ? EventTypeEnum::PrivateComment : EventTypeEnum::PublicComment
        );

        $issue->getEvents()->add($event);

        $comment = new Comment($event);

        $comment
            ->setBody($command->getBody())
            ->setPrivate($command->isPrivate())
        ;

        $this->issueRepository->persist($issue);
        $this->commentRepository->persist($comment);
    }
}
