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

use App\Message\Templates\UnlockTemplateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\Security\Voter\TemplateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class UnlockTemplateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TemplateRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UnlockTemplateCommand $command): void
    {
        /** @var null|\App\Entity\Template $template */
        $template = $this->repository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to unlock this template.');
        }

        if ($template->isLocked()) {
            $template->setLocked(false);

            $this->repository->persist($template);
        }
    }
}
