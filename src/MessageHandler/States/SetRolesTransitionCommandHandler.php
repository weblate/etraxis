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

use App\Entity\StateRoleTransition;
use App\Message\States\SetRolesTransitionCommand;
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
final class SetRolesTransitionCommandHandler implements CommandHandlerInterface
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
    public function __invoke(SetRolesTransitionCommand $command): void
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

        // Remove all roles which are supposed to not be granted for specified transition, but they currently are.
        /** @var StateRoleTransition[] $transitions */
        $transitions = array_filter(
            $fromState->getRoleTransitions()->toArray(),
            fn (StateRoleTransition $transition) => $transition->getToState()->getId() === $command->getToState()
        );

        foreach ($transitions as $transition) {
            if (!in_array($transition->getRole(), $command->getRoles(), true)) {
                $this->manager->remove($transition);
            }
        }

        // Add all roles which are supposed to be granted for specified transition, but they currently are not.
        $existingRoles = array_map(fn (StateRoleTransition $transition) => $transition->getRole(), $transitions);

        foreach ($command->getRoles() as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $transition = new StateRoleTransition($fromState, $toState, $role);
                $this->manager->persist($transition);
            }
        }
    }
}
