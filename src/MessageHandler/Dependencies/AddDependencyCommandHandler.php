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

namespace App\MessageHandler\Dependencies;

use App\Entity\Dependency;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Issue;
use App\Message\Dependencies\AddDependencyCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\DependencyRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\DependencyVoter;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
final class AddDependencyCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TranslatorInterface $translator,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly DependencyRepositoryInterface $dependencyRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(AddDependencyCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to add dependencies.');
        }

        /** @var null|Issue $dependency */
        $dependency = $this->issueRepository->find($command->getDependency());

        if (!$dependency) {
            throw new NotFoundHttpException('Unknown dependency.');
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $dependency)) {
            throw new NotFoundHttpException('Unknown dependency.');
        }

        if (in_array($issue, $this->dependencyRepository->findAllByIssue($dependency), true)) {
            throw new BadRequestHttpException($this->translator->trans('error.cross_dependency', locale: $user->getLocale()->value));
        }

        $dependencies = $this->dependencyRepository->findAllByIssue($issue);

        if (!in_array($dependency, $dependencies, true)) {
            $event = new Event($issue, $user, EventTypeEnum::DependencyAdded, $dependency->getFullId());
            $issue->getEvents()->add($event);

            $entity = new Dependency($event, $dependency);

            $this->issueRepository->persist($issue);
            $this->dependencyRepository->persist($entity);
        }
    }
}
