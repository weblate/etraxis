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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldGroupPermission;
use App\Entity\FieldRolePermission;
use App\Entity\State;
use App\Entity\StateGroupTransition;
use App\Entity\StateResponsibleGroup;
use App\Entity\StateRoleTransition;
use App\Entity\Template;
use App\Entity\TemplateGroupPermission;
use App\Entity\TemplateRolePermission;
use App\Message\Fields;
use App\Message\ListItems\CreateListItemCommand;
use App\Message\States\CreateStateCommand;
use App\Message\Templates\CloneTemplateCommand;
use App\Message\Templates\CreateTemplateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Command handler.
 */
final class CloneTemplateCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly StateRepositoryInterface $stateRepository,
        private readonly FieldRepositoryInterface $fieldRepository,
        private readonly ListItemRepositoryInterface $listRepository,
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
    public function __invoke(CloneTemplateCommand $command): Template
    {
        /** @var null|Template $sourceTemplate */
        $sourceTemplate = $this->templateRepository->find($command->getTemplate());

        if (!$sourceTemplate) {
            throw new NotFoundHttpException('Unknown template.');
        }

        // Clone template body.
        $createTemplateCommand = new CreateTemplateCommand(
            $command->getProject(),
            $command->getName(),
            $command->getPrefix(),
            $command->getDescription(),
            $command->getCriticalAge(),
            $command->getFrozenTime()
        );

        /** @var Template $clonedTemplate */
        $clonedTemplate = $this->commandBus->handleWithResult($createTemplateCommand);

        // Whether we clone to the same project.
        $isSameProject = $sourceTemplate->getProject() === $clonedTemplate->getProject();

        // Clone role permissions.
        foreach ($sourceTemplate->getRolePermissions() as $sourcePermission) {
            $clonedPermission = new TemplateRolePermission($clonedTemplate, $sourcePermission->getRole(), $sourcePermission->getPermission());
            $this->manager->persist($clonedPermission);
        }

        // Clone group permissions.
        foreach ($sourceTemplate->getGroupPermissions() as $sourcePermission) {
            // Do not clone permissions for local groups, if we clone the template to another project.
            if ($sourcePermission->getGroup()->isGlobal() || $isSameProject) {
                $clonedPermission = new TemplateGroupPermission($clonedTemplate, $sourcePermission->getGroup(), $sourcePermission->getPermission());
                $this->manager->persist($clonedPermission);
            }
        }

        // Clone states and fields.
        $clonedStates = [];

        foreach ($sourceTemplate->getStates() as $sourceState) {
            $clonedState    = $this->cloneStateStep1($sourceState, $clonedTemplate);
            $clonedStates[] = $clonedState;

            foreach ($sourceState->getFields() as $sourceField) {
                $this->cloneField($sourceField, $clonedState);
            }
        }

        // Clone state transitions.
        foreach ($sourceTemplate->getStates() as $sourceState) {
            $this->cloneStateStep2($sourceState, $clonedStates);
        }

        return $clonedTemplate;
    }

    /**
     * Clones specified state into another template (step 1 of 2).
     *
     * @param State    $sourceState    Original state
     * @param Template $clonedTemplate Target template
     *
     * @return State Clone of the state
     */
    private function cloneStateStep1(State $sourceState, Template $clonedTemplate): State
    {
        // Clone state body.
        $createStateCommand = new CreateStateCommand(
            $clonedTemplate->getId(),
            $sourceState->getName(),
            $sourceState->getType(),
            $sourceState->getResponsible(),
        );

        $this->commandBus->handle($createStateCommand);

        /** @var State $clonedState */
        $clonedState = $this->stateRepository->findOneByName($clonedTemplate->getId(), $sourceState->getName());

        // Whether we clone to the same project.
        $isSameProject = $sourceState->getTemplate()->getProject()->getId() === $clonedState->getTemplate()->getProject()->getId();

        // Clone responsible groups.
        foreach ($sourceState->getResponsibleGroups() as $sourceGroup) {
            // Do not clone responsibility for local groups, if we clone the template to another project.
            if ($sourceGroup->getGroup()->isGlobal() || $isSameProject) {
                $clonedGroup = new StateResponsibleGroup($clonedState, $sourceGroup->getGroup());
                $this->manager->persist($clonedGroup);
            }
        }

        return $clonedState;
    }

    /**
     * Clones specified state into another template (step 2 of 2).
     *
     * @param State   $sourceState  Original state
     * @param State[] $clonedStates All cloned states from the step 1
     */
    private function cloneStateStep2(State $sourceState, array $clonedStates): void
    {
        $newStates = new ArrayCollection($clonedStates);

        /** @var State $clonedState */
        $clonedState = $newStates->filter(fn (State $state) => $state->getName() === $sourceState->getName())->first();

        // Whether we clone to the same project.
        $isSameProject = $sourceState->getTemplate()->getProject()->getId() === $clonedState->getTemplate()->getProject()->getId();

        // Clone role transitions.
        foreach ($sourceState->getRoleTransitions() as $sourceTransition) {
            /** @var State $toState */
            $toState = $newStates->filter(fn (State $state) => $state->getName() === $sourceTransition->getToState()->getName())->first();

            $clonedTransition = new StateRoleTransition($clonedState, $toState, $sourceTransition->getRole());
            $this->manager->persist($clonedTransition);
        }

        // Clone group transitions.
        foreach ($sourceState->getGroupTransitions() as $sourceTransition) {
            // Do not clone responsibility for local groups, if we clone the template to another project.
            if ($sourceTransition->getGroup()->isGlobal() || $isSameProject) {
                /** @var State $toState */
                $toState = $newStates->filter(fn (State $state) => $state->getName() === $sourceTransition->getToState()->getName())->first();

                $clonedTransition = new StateGroupTransition($clonedState, $toState, $sourceTransition->getGroup());
                $this->manager->persist($clonedTransition);
            }
        }
    }

    /**
     * Clones specified field into another state.
     *
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     *
     * @param Field $sourceField Original field
     * @param State $clonedState Target state
     *
     * @return Field Clone of the field
     */
    private function cloneField(Field $sourceField, State $clonedState): Field
    {
        // Clone field body.
        $createFieldCommand = new Fields\CreateFieldCommand(
            $clonedState->getId(),
            $sourceField->getName(),
            $sourceField->getType(),
            $sourceField->getDescription(),
            $sourceField->isRequired(),
            $sourceField->getAllParameters(),
        );

        $this->commandBus->handle($createFieldCommand);

        /** @var Field $clonedField */
        $clonedField = $this->fieldRepository->findOneByName($clonedState->getId(), $sourceField->getName());

        // Whether we clone to the same project.
        $isSameProject = $sourceField->getState()->getTemplate()->getProject()->getId() === $clonedField->getState()->getTemplate()->getProject()->getId();

        // Clone role permissions.
        foreach ($sourceField->getRolePermissions() as $sourcePermission) {
            $clonedPermission = new FieldRolePermission($clonedField, $sourcePermission->getRole(), $sourcePermission->getPermission());
            $this->manager->persist($clonedPermission);
        }

        // Clone group permissions.
        foreach ($sourceField->getGroupPermissions() as $sourcePermission) {
            // Do not clone permissions for local groups, if we clone the template to another project.
            if ($sourcePermission->getGroup()->isGlobal() || $isSameProject) {
                $clonedPermission = new FieldGroupPermission($clonedField, $sourcePermission->getGroup(), $sourcePermission->getPermission());
                $this->manager->persist($clonedPermission);
            }
        }

        // Clone list items.
        if (FieldTypeEnum::List === $sourceField->getType()) {
            foreach ($this->listRepository->findAllByField($sourceField) as $sourceItem) {
                $createListItemCommand = new CreateListItemCommand(
                    $clonedField->getId(),
                    $sourceItem->getValue(),
                    $sourceItem->getText()
                );

                $this->commandBus->handle($createListItemCommand);
            }
        }

        return $clonedField;
    }
}
