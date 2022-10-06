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

use App\Message\Projects\ResumeProjectCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\Security\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class ResumeProjectCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ProjectRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ResumeProjectCommand $command): void
    {
        /** @var null|\App\Entity\Project $project */
        $project = $this->repository->find($command->getProject());

        if (!$project) {
            throw new NotFoundHttpException('Unknown project.');
        }

        if (!$this->security->isGranted(ProjectVoter::RESUME_PROJECT, $project)) {
            throw new AccessDeniedHttpException('You are not allowed to resume this project.');
        }

        if ($project->isSuspended()) {
            $project->setSuspended(false);

            $this->repository->persist($project);
        }
    }
}
