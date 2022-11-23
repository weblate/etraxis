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

namespace App\MessageHandler\Fields;

use App\Entity\Field;
use App\Message\Fields\CreateFieldCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Security\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
final class CreateFieldCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly StateRepositoryInterface $stateRepository,
        private readonly FieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(CreateFieldCommand $command): Field
    {
        /** @var null|\App\Entity\State $state */
        $state = $this->stateRepository->find($command->getState());

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(FieldVoter::CREATE_FIELD, $state)) {
            throw new AccessDeniedHttpException('You are not allowed to create new field.');
        }

        $field = new Field($state, $command->getType());

        $field
            ->setName($command->getName())
            ->setDescription($command->getDescription())
            ->setRequired($command->isRequired())
            ->setPosition(count($state->getFields()) + 1)
        ;

        $strategy = $field->getStrategy();

        foreach ($command->getParameters() ?? [] as $parameter => $value) {
            $strategy->setParameter($parameter, $value);
        }

        $errors = $this->validator->validate($field->getAllParameters(), new Assert\Collection([
            'fields'             => $strategy->getParametersValidationConstraints($this->translator),
            'allowExtraFields'   => true,
            'allowMissingFields' => true,
        ]));

        if (count($errors)) {
            throw new ValidationFailedException($command, $errors);
        }

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->fieldRepository->persist($field);

        return $field;
    }
}
