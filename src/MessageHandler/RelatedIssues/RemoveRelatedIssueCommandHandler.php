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

namespace App\MessageHandler\RelatedIssues;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Issue;
use App\Message\RelatedIssues\RemoveRelatedIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\RelatedIssueRepositoryInterface;
use App\Security\Voter\IssueVoter;
use App\Security\Voter\RelatedIssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class RemoveRelatedIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly RelatedIssueRepositoryInterface $relatedIssueRepository
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
    public function __invoke(RemoveRelatedIssueCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|Issue $issue */
        $issue = $this->issueRepository->find($command->getIssue());

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(RelatedIssueVoter::REMOVE_RELATED_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to remove related issues.');
        }

        /** @var null|Issue $relatedIssue */
        $relatedIssue = $this->issueRepository->find($command->getRelatedIssue());

        if (!$relatedIssue) {
            throw new NotFoundHttpException('Unknown related issue.');
        }

        if (!$this->security->isGranted(IssueVoter::VIEW_ISSUE, $relatedIssue)) {
            throw new NotFoundHttpException('Unknown related issue.');
        }

        $relatedIssues = $this->relatedIssueRepository->getRelatedIssues($issue);

        if (in_array($relatedIssue, $relatedIssues, true)) {
            $event = new Event($issue, $user, EventTypeEnum::RelatedIssueRemoved, $relatedIssue->getFullId());
            $issue->getEvents()->add($event);

            $query = $this->relatedIssueRepository->createQueryBuilder('relatedIssue')
                ->innerJoin('relatedIssue.event', 'event')
                ->where('event.issue = :issue')
                ->andWhere('relatedIssue.issue = :relatedIssue')
                ->setParameter('issue', $issue)
                ->setParameter('relatedIssue', $relatedIssue)
            ;

            /** @var \App\Entity\RelatedIssue $entity */
            $entity = $query->getQuery()->getSingleResult();

            $this->issueRepository->persist($issue);
            $this->relatedIssueRepository->remove($entity);
        }
    }
}
