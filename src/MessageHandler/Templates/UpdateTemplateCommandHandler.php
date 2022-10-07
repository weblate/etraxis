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

namespace App\MessageHandler\Templates;

use App\Message\Templates\UpdateTemplateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
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
final class UpdateTemplateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly TemplateRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateTemplateCommand $command): void
    {
        /** @var null|\App\Entity\Template $template */
        $template = $this->repository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to update this template.');
        }

        $template
            ->setName($command->getName())
            ->setPrefix($command->getPrefix())
            ->setDescription($command->getDescription())
            ->setCriticalAge($command->getCriticalAge())
            ->setFrozenTime($command->getFrozenTime())
        ;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($template);
    }
}
