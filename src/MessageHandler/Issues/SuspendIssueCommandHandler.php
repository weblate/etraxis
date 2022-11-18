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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Message\Issues\SuspendIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SuspendIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SuspendIssueCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->repository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to suspend this issue.');
        }

        $date = date_create_from_format('Y-m-d', $command->getDate(), timezone_open($user->getTimezone()) ?: null);
        $date->setTime(0, 0);

        if ($date->getTimestamp() < time()) {
            throw new BadRequestHttpException('Date must be in future.');
        }

        $event = new Event($issue, $user, EventTypeEnum::IssueSuspended);
        $issue->getEvents()->add($event);

        $issue->touch();
        $issue->suspend($date->getTimestamp());

        $this->repository->persist($issue);
    }
}
