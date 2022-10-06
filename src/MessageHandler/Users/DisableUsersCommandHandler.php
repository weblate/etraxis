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

use App\Entity\User;
use App\Message\Users\DisableUsersCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DisableUsersCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly UserRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(DisableUsersCommand $command): void
    {
        $ids = array_unique($command->getUsers());

        /** @var User[] $accounts */
        $accounts = $this->repository->findBy([
            'id' => $ids,
        ]);

        if (count($accounts) !== count($ids)) {
            throw new NotFoundHttpException('Unknown user.');
        }

        $accounts = array_filter($accounts, fn (User $user) => !$user->isDisabled());

        foreach ($accounts as $account) {
            if (!$this->security->isGranted(UserVoter::DISABLE_USER, $account)) {
                throw new AccessDeniedHttpException('You are not allowed to disable one of these users.');
            }
        }

        $query = $this->manager->createQuery('
            UPDATE App:User u
            SET u.disabled = :state
            WHERE u.id IN (:ids)
        ');

        $query->execute([
            'ids'   => $ids,
            'state' => true,
        ]);
    }
}
