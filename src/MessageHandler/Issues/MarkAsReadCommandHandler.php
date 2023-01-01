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

use App\Entity\LastRead;
use App\Message\Issues\MarkAsReadCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\LastReadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
final class MarkAsReadCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IssueRepositoryInterface $issueRepository,
        private readonly LastReadRepositoryInterface $lastReadRepository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(MarkAsReadCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $issues = $this->issueRepository->reduceByUser($user, $command->getIssues());

        // Delete existing reads of resulted issues.
        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(LastRead::class, 'read')
            ->where('read.user = :user')
            ->andWhere($query->expr()->in('read.issue', ':issues'))
        ;

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $issues,
        ]);

        // Mark resulted issues as read.
        foreach ($issues as $issue) {
            $read = new LastRead($issue, $user);
            $this->lastReadRepository->persist($read);
        }
    }
}
