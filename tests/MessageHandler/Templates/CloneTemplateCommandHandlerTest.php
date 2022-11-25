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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldGroupPermission;
use App\Entity\FieldRolePermission;
use App\Entity\ListItem;
use App\Entity\Project;
use App\Entity\State;
use App\Entity\StateGroupTransition;
use App\Entity\StateResponsibleGroup;
use App\Entity\StateRoleTransition;
use App\Entity\Template;
use App\Entity\TemplateGroupPermission;
use App\Entity\TemplateRolePermission;
use App\LoginTrait;
use App\Message\Templates\CloneTemplateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\CloneTemplateCommandHandler::__invoke
 * @covers \App\MessageHandler\Templates\CloneTemplateCommandHandler::cloneField
 * @covers \App\MessageHandler\Templates\CloneTemplateCommandHandler::cloneStateStep1
 * @covers \App\MessageHandler\Templates\CloneTemplateCommandHandler::cloneStateStep2
 */
final class CloneTemplateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface         $commandBus;
    private TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $sourceTemplate */
        $sourceTemplate = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $totalTemplateRolePermissions  = count($this->doctrine->getRepository(TemplateRolePermission::class)->findAll());
        $totalTemplateGroupPermissions = count($this->doctrine->getRepository(TemplateGroupPermission::class)->findAll());
        $totalStates                   = count($this->doctrine->getRepository(State::class)->findAll());
        $totalStateResponsibleGroups   = count($this->doctrine->getRepository(StateResponsibleGroup::class)->findAll());
        $totalStateRoleTransitions     = count($this->doctrine->getRepository(StateRoleTransition::class)->findAll());
        $totalStateGroupTransitions    = count($this->doctrine->getRepository(StateGroupTransition::class)->findAll());
        $totalFields                   = count($this->doctrine->getRepository(Field::class)->findAll());
        $totalFieldRolePermissions     = count($this->doctrine->getRepository(FieldRolePermission::class)->findAll());
        $totalFieldGroupPermissions    = count($this->doctrine->getRepository(FieldGroupPermission::class)->findAll());
        $totalListItems                = count($this->doctrine->getRepository(ListItem::class)->findAll());

        $totalTemplateRolePermissions  += $sourceTemplate->getRolePermissions()->count();
        $totalTemplateGroupPermissions += $sourceTemplate->getGroupPermissions()->count();
        $totalStates                   += $sourceTemplate->getStates()->count();

        foreach ($sourceTemplate->getStates() as $sourceState) {
            $totalStateResponsibleGroups += $sourceState->getResponsibleGroups()->count();
            $totalStateRoleTransitions   += $sourceState->getRoleTransitions()->count();
            $totalStateGroupTransitions  += $sourceState->getGroupTransitions()->count();
            $totalFields                 += $sourceState->getFields()->count();

            foreach ($sourceState->getFields() as $sourceField) {
                $totalFieldRolePermissions  += $sourceField->getRolePermissions()->count();
                $totalFieldGroupPermissions += $sourceField->getGroupPermissions()->count();

                if (FieldTypeEnum::List === $sourceField->getType()) {
                    $totalListItems += count($this->doctrine->getRepository(ListItem::class)->findAllByField($sourceField));
                }
            }
        }

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertNull($template);

        $command = new CloneTemplateCommand($sourceTemplate->getId(), $project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $result = $this->commandBus->handleWithResult($command);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['name' => 'Bugfix']);
        self::assertInstanceOf(Template::class, $template);
        self::assertSame($template, $result);

        self::assertSame($project, $template->getProject());
        self::assertSame('Bugfix', $template->getName());
        self::assertSame('bug', $template->getPrefix());
        self::assertSame('Error reports', $template->getDescription());
        self::assertSame(5, $template->getCriticalAge());
        self::assertSame(10, $template->getFrozenTime());
        self::assertTrue($template->isLocked());

        self::assertCount($totalTemplateRolePermissions, $this->doctrine->getRepository(TemplateRolePermission::class)->findAll());
        self::assertCount($totalTemplateGroupPermissions, $this->doctrine->getRepository(TemplateGroupPermission::class)->findAll());
        self::assertCount($totalStates, $this->doctrine->getRepository(State::class)->findAll());
        self::assertCount($totalStateResponsibleGroups, $this->doctrine->getRepository(StateResponsibleGroup::class)->findAll());
        self::assertCount($totalStateRoleTransitions, $this->doctrine->getRepository(StateRoleTransition::class)->findAll());
        self::assertCount($totalStateGroupTransitions, $this->doctrine->getRepository(StateGroupTransition::class)->findAll());
        self::assertCount($totalFields, $this->doctrine->getRepository(Field::class)->findAll());
        self::assertCount($totalFieldRolePermissions, $this->doctrine->getRepository(FieldRolePermission::class)->findAll());
        self::assertCount($totalFieldGroupPermissions, $this->doctrine->getRepository(FieldGroupPermission::class)->findAll());
        self::assertCount($totalListItems, $this->doctrine->getRepository(ListItem::class)->findAll());
    }

    public function testValidationEmptyName(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand(
            $template->getId(),
            $project->getId(),
            '',
            'bug',
            'Error reports',
            5,
            10
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand(
            $template->getId(),
            $project->getId(),
            str_pad('', Template::MAX_NAME + 1),
            'bug',
            'Error reports',
            5,
            10
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 50 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyPrefix(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand(
            $template->getId(),
            $project->getId(),
            'Bugfix',
            '',
            'Error reports',
            5,
            10
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationPrefixLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand(
            $template->getId(),
            $project->getId(),
            'Bugfix',
            str_pad('', Template::MAX_PREFIX + 1),
            'Error reports',
            5,
            10
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 5 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationDescriptionLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand(
            $template->getId(),
            $project->getId(),
            'Bugfix',
            'bug',
            str_pad('', Template::MAX_DESCRIPTION + 1),
            5,
            10
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 100 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationCriticalAge(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), $project->getId(), 'Bugfix', 'bug', 'Error reports', 0, 10);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should be 1 or more.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationFrozenTime(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), $project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 0);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should be 1 or more.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownProject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), self::UNKNOWN_ENTITY_ID, 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CloneTemplateCommand(self::UNKNOWN_ENTITY_ID, $project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new template.');

        $this->loginUser('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), $project->getId(), 'Bugfix', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), $project->getId(), 'Development', 'bug', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }

    public function testPrefixConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginUser('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->repository->findOneBy(['project' => $project, 'name' => 'Development']);

        $command = new CloneTemplateCommand($template->getId(), $project->getId(), 'Bugfix', 'task', 'Error reports', 5, 10);

        $this->commandBus->handle($command);
    }
}
