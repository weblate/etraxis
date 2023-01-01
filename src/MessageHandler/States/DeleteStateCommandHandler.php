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

use App\Message\States\DeleteStateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Security\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DeleteStateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly StateRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteStateCommand $command): void
    {
        /** @var null|\App\Entity\State $state */
        $state = $this->repository->find($command->getState());

        if ($state) {
            if (!$this->security->isGranted(StateVoter::DELETE_STATE, $state)) {
                throw new AccessDeniedHttpException('You are not allowed to delete this state.');
            }

            $this->repository->remove($state);
        }
    }
}
