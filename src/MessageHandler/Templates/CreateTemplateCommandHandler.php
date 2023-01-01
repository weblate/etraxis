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

namespace App\MessageHandler\Templates;

use App\Entity\Template;
use App\Message\Templates\CreateTemplateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ProjectRepositoryInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\Security\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class CreateTemplateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly TemplateRepositoryInterface $templateRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(CreateTemplateCommand $command): Template
    {
        /** @var null|\App\Entity\Project $project */
        $project = $this->projectRepository->find($command->getProject());

        if (!$project) {
            throw new NotFoundHttpException('Unknown project.');
        }

        if (!$this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project)) {
            throw new AccessDeniedHttpException('You are not allowed to create new template.');
        }

        $template = new Template($project);

        $template
            ->setName($command->getName())
            ->setPrefix($command->getPrefix())
            ->setDescription($command->getDescription())
            ->setCriticalAge($command->getCriticalAge())
            ->setFrozenTime($command->getFrozenTime())
            ->setLocked(true)
        ;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->templateRepository->persist($template);

        return $template;
    }
}
