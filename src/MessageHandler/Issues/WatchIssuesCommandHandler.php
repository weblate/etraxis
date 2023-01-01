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

namespace App\MessageHandler\Issues;

use App\Entity\Watcher;
use App\Message\Issues\WatchIssuesCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\WatcherRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
final class WatchIssuesCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly WatcherRepositoryInterface $watcherRepository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(WatchIssuesCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $issues = $this->issueRepository->reduceByUser($user, $command->getIssues());

        // Delete existing watchings for resulted issues.
        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(Watcher::class, 'w')
            ->where('w.user = :user')
            ->andWhere($query->expr()->in('w.issue', ':issues'))
        ;

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $issues,
        ]);

        // Watch resulted issues.
        foreach ($issues as $issue) {
            $watcher = new Watcher($issue, $user);
            $this->watcherRepository->persist($watcher);
        }
    }
}
