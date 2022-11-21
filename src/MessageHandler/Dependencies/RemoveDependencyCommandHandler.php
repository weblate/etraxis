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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Issue;
use App\Message\Dependencies\RemoveDependencyCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\DependencyRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Security\Voter\DependencyVoter;
use App\Security\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class RemoveDependencyCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly DependencyRepositoryInterface $dependencyRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __invoke(RemoveDependencyCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to remove dependencies.');
        }

        /** @var null|Issue $dependency */
        $dependency = $this->issueRepository->find($command->getDependency());

        if (!$dependency) {
            throw new NotFoundHttpException('Unknown dependency.');
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $dependency)) {
            throw new NotFoundHttpException('Unknown dependency.');
        }

        $dependencies = $this->dependencyRepository->findAllByIssue($issue);

        if (in_array($dependency, $dependencies, true)) {
            $event = new Event($issue, $user, EventTypeEnum::DependencyRemoved, $dependency->getFullId());
            $issue->getEvents()->add($event);

            $query = $this->dependencyRepository->createQueryBuilder('dependency')
                ->innerJoin('dependency.event', 'event')
                ->where('event.issue = :issue')
                ->andWhere('dependency.issue = :dependency')
                ->setParameter('issue', $issue)
                ->setParameter('dependency', $dependency)
            ;

            /** @var \App\Entity\Dependency $entity */
            $entity = $query->getQuery()->getSingleResult();

            $this->issueRepository->persist($issue);
            $this->dependencyRepository->remove($entity);
        }
    }
}
