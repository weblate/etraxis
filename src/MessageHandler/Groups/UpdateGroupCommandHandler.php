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

namespace App\MessageHandler\Groups;

use App\Message\Groups\UpdateGroupCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\Security\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class UpdateGroupCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly GroupRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateGroupCommand $command): void
    {
        /** @var null|\App\Entity\Group $group */
        $group = $this->repository->find($command->getGroup());

        if (!$group) {
            throw new NotFoundHttpException('Unknown group.');
        }

        if (!$this->security->isGranted(GroupVoter::UPDATE_GROUP, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to update this group.');
        }

        $group
            ->setName($command->getName())
            ->setDescription($command->getDescription())
        ;

        $errors = $this->validator->validate($group);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($group);
    }
}
