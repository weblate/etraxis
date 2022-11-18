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

namespace App\MessageHandler\Issues;

use App\Message\Issues\ReassignIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class ReassignIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepositoryInterface $userRepository,
        private readonly IssueRepositoryInterface $issueRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ReassignIssueCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        $responsible = $this->userRepository->find($command->getResponsible());

        if (!$responsible) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to reassign this issue.');
        }

        if ($issue->getResponsible() !== $responsible) {
            if (!$this->issueRepository->reassignIssue($user, $issue, $responsible)) {
                throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
            }

            $this->issueRepository->persist($issue);
        }
    }
}
