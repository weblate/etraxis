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
use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\Transition;
use App\Message\Issues\ChangeStateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Repository\Contracts\TransitionRepositoryInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class ChangeStateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepositoryInterface $userRepository,
        private readonly StateRepositoryInterface $stateRepository,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly TransitionRepositoryInterface $transitionRepository,
        private readonly FieldValueRepositoryInterface $valueRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ChangeStateCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        /** @var null|\App\Entity\State $state */
        $state = $this->stateRepository->find($command->getState());

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(IssueVoter::CHANGE_STATE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to change the current state.');
        }

        if (!in_array($state, $this->issueRepository->getTransitionsByUser($issue, $user), true)) {
            throw new AccessDeniedHttpException('You are not allowed to change the current state to specified one.');
        }

        $errors = $this->valueRepository->validateFieldValues(
            $state->getFields()->toArray(),
            $command->getFields() ?? []
        );

        if (count($errors)) {
            throw new ValidationFailedException($command, $errors);
        }

        if (!$issue->isClosed() && $state->isFinal()) {
            $eventType = EventTypeEnum::IssueClosed;
        } elseif ($issue->isClosed() && !$state->isFinal()) {
            $eventType = EventTypeEnum::IssueReopened;
        } else {
            $eventType = EventTypeEnum::StateChanged;
        }

        $event      = new Event($issue, $user, $eventType, $state->getName());
        $transition = new Transition($event, $state);

        $issue->touch();
        $issue->setState($state);
        $issue->getEvents()->add($event);

        if (StateResponsibleEnum::Assign === $issue->getState()->getResponsible()) {
            if (null === $command->getResponsible()) {
                throw new BadRequestHttpException('Responsible is required.');
            }

            $responsible = $this->userRepository->find($command->getResponsible());

            if (!$responsible) {
                throw new NotFoundHttpException('Unknown responsible.');
            }

            if (!$this->issueRepository->assignIssue($user, $issue, $responsible)) {
                throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
            }
        } elseif (StateResponsibleEnum::Remove === $issue->getState()->getResponsible()) {
            $issue->setResponsible(null);
        }

        $this->issueRepository->persist($issue);
        $this->transitionRepository->persist($transition);

        foreach ($issue->getState()->getFields() as $field) {
            $fieldValue = new FieldValue($transition, $field, null);

            $this->valueRepository->setFieldValue($fieldValue, $command->getField($field->getId()));
            $this->valueRepository->persist($fieldValue);
        }
    }
}
