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

namespace App\MessageHandler\Projects;

use App\Entity\Project;
use App\Message\Projects\CreateProjectCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\Security\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class CreateProjectCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly ProjectRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    public function __invoke(CreateProjectCommand $command): void
    {
        if (!$this->security->isGranted(ProjectVoter::CREATE_PROJECT)) {
            throw new AccessDeniedHttpException('You are not allowed to create new project.');
        }

        $project = new Project();

        $project
            ->setName($command->getName())
            ->setDescription($command->getDescription())
            ->setSuspended($command->isSuspended())
        ;

        $errors = $this->validator->validate($project);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($project);
    }
}
