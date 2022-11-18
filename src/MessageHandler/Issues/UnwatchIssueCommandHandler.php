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

use App\Message\Issues\UnwatchIssueCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\WatcherRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
final class UnwatchIssueCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly WatcherRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(UnwatchIssueCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var null|\App\Entity\Watcher $watcher */
        $watcher = $this->repository->findOneBy([
            'user'  => $user,
            'issue' => $command->getIssue(),
        ]);

        if ($watcher) {
            $this->repository->remove($watcher);
        }
    }
}
