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

namespace App\MessageHandler\Groups;

use App\Message\Groups\DeleteGroupCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\Security\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DeleteGroupCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly GroupRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteGroupCommand $command): void
    {
        /** @var null|\App\Entity\Group $group */
        $group = $this->repository->find($command->getGroup());

        if ($group) {
            if (!$this->security->isGranted(GroupVoter::DELETE_GROUP, $group)) {
                throw new AccessDeniedHttpException('You are not allowed to delete this group.');
            }

            $this->repository->remove($group);
        }
    }
}
