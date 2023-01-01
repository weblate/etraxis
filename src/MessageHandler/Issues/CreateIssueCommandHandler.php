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

namespace App\MessageHandler\Issues;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\Transition;
use App\Message\Issues\CreateIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
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
final class CreateIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
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
    public function __invoke(CreateIssueCommand $command): Issue
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Template $template */
        $template = $this->templateRepository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(IssueVoter::CREATE_ISSUE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new issue.');
        }

        $errors = $this->valueRepository->validateFieldValues(
            $template->getInitialState()->getFields()->toArray(),
            $command->getFields() ?? []
        );

        if (count($errors)) {
            throw new ValidationFailedException($command, $errors);
        }

        $issue      = new Issue($template, $user);
        $event      = new Event($issue, $user, EventTypeEnum::IssueCreated, $issue->getState()->getName());
        $transition = new Transition($event, $issue->getState());

        $issue->setSubject($command->getSubject());
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
        }

        $this->issueRepository->persist($issue);
        $this->transitionRepository->persist($transition);

        foreach ($issue->getState()->getFields() as $field) {
            $fieldValue = new FieldValue($transition, $field, null);

            $this->valueRepository->setFieldValue($fieldValue, $command->getField($field->getId()));
            $this->valueRepository->persist($fieldValue);
        }

        return $issue;
    }
}
