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

use App\Entity\Change;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Message\Issues\UpdateIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ChangeRepositoryInterface;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\StringValueRepositoryInterface;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class UpdateIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly ChangeRepositoryInterface $changeRepository,
        private readonly StringValueRepositoryInterface $stringRepository,
        private readonly FieldValueRepositoryInterface $valueRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws ValidationFailedException
     */
    public function __invoke(UpdateIssueCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to update this issue.');
        }

        $subject = $command->getSubject() ?? '';
        $values  = $command->getFields()  ?? [];

        if (0 !== mb_strlen($subject) || 0 !== count($values)) {
            $event = new Event($issue, $user, EventTypeEnum::IssueEdited);
            $issue->getEvents()->add($event);
            $issue->touch();

            $isChanged = false;

            // Change the subject.
            if (0 !== mb_strlen($subject) && $issue->getSubject() !== $subject) {
                $oldValue = $this->stringRepository->get($issue->getSubject())->getId();
                $newValue = $this->stringRepository->get($subject)->getId();

                $change = new Change($event, null, $oldValue, $newValue);
                $issue->setSubject($subject);

                $this->changeRepository->persist($change);
                $isChanged = true;
            }

            // Change the fields.
            if (0 !== count($values)) {
                $fieldValues = array_filter(
                    $this->valueRepository->getLatestValues($issue, $user),
                    fn (FieldValue $fieldValue) => array_key_exists($fieldValue->getField()->getId(), $values)
                );

                $fields = array_map(fn (FieldValue $fieldValue) => $fieldValue->getField(), $fieldValues);

                $context = [];

                foreach ($fieldValues as $fieldValue) {
                    if (FieldTypeEnum::Date === $fieldValue->getField()->getType()) {
                        $context[$fieldValue->getField()->getId()] = $fieldValue->getTransition()->getEvent()->getCreatedAt();
                    }
                }

                $errors = $this->valueRepository->validateFieldValues($fields, $values, $context);

                if (count($errors)) {
                    throw new ValidationFailedException($command, $errors);
                }

                foreach ($fieldValues as $fieldValue) {
                    $oldValue = $fieldValue->getValue();
                    $this->valueRepository->setFieldValue($fieldValue, $values[$fieldValue->getField()->getId()]);
                    $newValue = $fieldValue->getValue();

                    if ($oldValue !== $newValue) {
                        $change = new Change($event, $fieldValue->getField(), $oldValue, $newValue);
                        $this->valueRepository->persist($fieldValue);
                        $this->changeRepository->persist($change);
                        $isChanged = true;
                    }
                }
            }

            if ($isChanged) {
                $this->issueRepository->persist($issue);
            } else {
                $this->issueRepository->refresh($issue);
            }
        }
    }
}
