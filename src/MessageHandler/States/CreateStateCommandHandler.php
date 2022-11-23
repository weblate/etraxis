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

namespace App\MessageHandler\States;

use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use App\Message\States\CreateStateCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\Security\Voter\StateVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class CreateStateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly StateRepositoryInterface $stateRepository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(CreateStateCommand $command): State
    {
        /** @var null|\App\Entity\Template $template */
        $template = $this->templateRepository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(StateVoter::CREATE_STATE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new state.');
        }

        $state = new State($template, $command->getType());

        $state
            ->setName($command->getName())
            ->setResponsible($command->getResponsible())
        ;

        $errors = $this->validator->validate($state);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        // Only one initial state is allowed per template.
        if (StateTypeEnum::Initial === $command->getType()) {
            $query = $this->manager->createQuery('
                UPDATE App:State state
                SET state.type = :intermediate
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template'     => $template,
                'initial'      => StateTypeEnum::Initial,
                'intermediate' => StateTypeEnum::Intermediate,
            ]);
        }

        $this->stateRepository->persist($state);

        return $state;
    }
}
