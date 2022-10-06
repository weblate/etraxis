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

namespace App\MessageHandler\Users;

use App\Entity\Group;
use App\Message\Users\RemoveGroupsCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class RemoveGroupsCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(RemoveGroupsCommand $command): void
    {
        /** @var null|\App\Entity\User $user */
        $user = $this->userRepository->find($command->getUser());

        if (!$user) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(UserVoter::MANAGE_USER_GROUPS, $user)) {
            throw new AccessDeniedHttpException('You are not allowed to manage this user.');
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
        ;

        /** @var Group[] $groups */
        $groups = $query->getQuery()->execute([
            'groups' => $command->getGroups(),
        ]);

        foreach ($groups as $group) {
            $group->removeMember($user);
            $this->groupRepository->persist($group);
        }
    }
}
