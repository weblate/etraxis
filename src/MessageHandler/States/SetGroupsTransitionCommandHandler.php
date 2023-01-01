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

namespace App\MessageHandler\States;

use App\Entity\Group;
use App\Entity\StateGroupTransition;
use App\Message\States\SetGroupsTransitionCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Security\Voter\StateVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SetGroupsTransitionCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly StateRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetGroupsTransitionCommand $command): void
    {
        /** @var null|\App\Entity\State $fromState */
        $fromState = $this->repository->find($command->getFromState());

        /** @var null|\App\Entity\State $toState */
        $toState = $this->repository->find($command->getToState());

        if (!$fromState || !$toState) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $fromState)) {
            throw new AccessDeniedHttpException('You are not allowed to set state transitions.');
        }

        // Retrieve all groups specified in the command.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
        ;

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->getGroups(),
        ]);

        // Remove all groups which are supposed to not be granted for specified transition, but they currently are.
        /** @var StateGroupTransition[] $transitions */
        $transitions = array_filter(
            $fromState->getGroupTransitions()->toArray(),
            fn (StateGroupTransition $transition) => $transition->getToState()->getId() === $command->getToState()
        );

        foreach ($transitions as $transition) {
            if (!in_array($transition->getGroup(), $requestedGroups, true)) {
                $this->manager->remove($transition);
            }
        }

        // Add all groups which are supposed to be granted for specified transition, but they currently are not.
        $existingGroups = array_map(fn (StateGroupTransition $transition) => $transition->getGroup(), $transitions);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $transition = new StateGroupTransition($fromState, $toState, $group);
                $this->manager->persist($transition);
            }
        }
    }
}
