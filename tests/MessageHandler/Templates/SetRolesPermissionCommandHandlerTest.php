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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Template;
use App\Entity\TemplateRolePermission;
use App\LoginTrait;
use App\Message\Templates\SetRolesPermissionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\SetRolesPermissionCommandHandler::__invoke
 */
final class SetRolesPermissionCommandHandlerTest extends TransactionalTestCase
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

        $before = [
            TemplatePermissionEnum::AddComments,
            TemplatePermissionEnum::AttachFiles,
            TemplatePermissionEnum::EditIssues,
        ];

        $after = [
            TemplatePermissionEnum::AddComments,
            TemplatePermissionEnum::PrivateComments,
            TemplatePermissionEnum::EditIssues,
        ];

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertSame($before, $this->permissionsToArray($template->getRolePermissions(), SystemRoleEnum::Author));

        $command = new SetRolesPermissionCommand($template->getId(), TemplatePermissionEnum::PrivateComments, [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $command = new SetRolesPermissionCommand($template->getId(), TemplatePermissionEnum::AttachFiles, [
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertSame($after, $this->permissionsToArray($template->getRolePermissions(), SystemRoleEnum::Author));
    }

    public function testValidationInvalidRoles(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $command = new SetRolesPermissionCommand($template->getId(), TemplatePermissionEnum::PrivateComments, [
            'foo',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('One or more of the given values is invalid.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set template permissions.');

        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $command = new SetRolesPermissionCommand($template->getId(), TemplatePermissionEnum::PrivateComments, [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        $command = new SetRolesPermissionCommand(self::UNKNOWN_ENTITY_ID, TemplatePermissionEnum::PrivateComments, [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    private function permissionsToArray(Collection $permissions, SystemRoleEnum $role): array
    {
        $filtered = array_filter($permissions->toArray(), fn (TemplateRolePermission $permission) => $permission->getRole() === $role);
        $result   = array_map(fn (TemplateRolePermission $permission) => $permission->getPermission(), $filtered);

        usort($result, fn (TemplatePermissionEnum $p1, TemplatePermissionEnum $p2) => strcmp($p1->value, $p2->value));

        return $result;
    }
}
