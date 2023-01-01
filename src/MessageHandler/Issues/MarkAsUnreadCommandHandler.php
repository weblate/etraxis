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
use App\Message\Issues\MarkAsUnreadCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
final class MarkAsUnreadCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(MarkAsUnreadCommand $command): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(LastRead::class, 'read')
            ->where('read.user = :user')
            ->andWhere($query->expr()->in('read.issue', ':issues'))
        ;

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $command->getIssues(),
        ]);
    }
}
