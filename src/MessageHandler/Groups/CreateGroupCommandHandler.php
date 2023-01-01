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

use App\Entity\Group;
use App\Message\Groups\CreateGroupCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\Security\Voter\GroupVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class CreateGroupCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly GroupRepositoryInterface $groupRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(CreateGroupCommand $command): Group
    {
        if (!$this->security->isGranted(GroupVoter::CREATE_GROUP)) {
            throw new AccessDeniedHttpException('You are not allowed to create new group.');
        }

        /** @var null|\App\Entity\Project $project */
        $project = null;

        if (null !== $command->getProject()) {
            $project = $this->projectRepository->find($command->getProject());

            if (!$project) {
                throw new NotFoundHttpException('Unknown project.');
            }
        }

        $group = new Group($project);

        $group
            ->setName($command->getName())
            ->setDescription($command->getDescription())
        ;

        $errors = $this->validator->validate($group);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->groupRepository->persist($group);

        return $group;
    }
}
