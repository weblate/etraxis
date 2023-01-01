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

use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use App\Message\States\SetInitialStateCommand;
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
final class SetInitialStateCommandHandler implements CommandHandlerInterface
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
    public function __invoke(SetInitialStateCommand $command): void
    {
        /** @var null|State $state */
        $state = $this->repository->find($command->getState());

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(StateVoter::SET_INITIAL_STATE, $state)) {
            throw new AccessDeniedHttpException('You are not allowed to set initial state.');
        }

        if (StateTypeEnum::Initial !== $state->getType()) {
            // Only one initial state is allowed per template.
            $query = $this->manager->createQuery('
                UPDATE App:State state
                SET state.type = :intermediate
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template'     => $state->getTemplate(),
                'initial'      => StateTypeEnum::Initial,
                'intermediate' => StateTypeEnum::Intermediate,
            ]);

            $reflection = new \ReflectionProperty(State::class, 'type');
            $reflection->setValue($state, StateTypeEnum::Initial->value);

            $this->repository->persist($state);
        }
    }
}
