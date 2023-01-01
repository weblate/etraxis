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

use App\Entity\User;
use App\Message\Groups\RemoveMembersCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\Security\Voter\GroupVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class RemoveMembersCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly GroupRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(RemoveMembersCommand $command): void
    {
        /** @var null|\App\Entity\Group $group */
        $group = $this->repository->find($command->getGroup());

        if (!$group) {
            throw new NotFoundHttpException('Unknown group.');
        }

        if (!$this->security->isGranted(GroupVoter::MANAGE_GROUP_MEMBERS, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to manage this group.');
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('user')
            ->from(User::class, 'user')
            ->where($query->expr()->in('user.id', ':users'))
        ;

        /** @var User[] $users */
        $users = $query->getQuery()->execute([
            'users' => $command->getUsers(),
        ]);

        foreach ($users as $user) {
            $group->removeMember($user);
        }

        $this->repository->persist($group);
    }
}
