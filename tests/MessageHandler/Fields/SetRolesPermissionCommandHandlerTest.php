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

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Field;
use App\Entity\FieldRolePermission;
use App\LoginTrait;
use App\Message\Fields\SetRolesPermissionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\SetRolesPermissionCommandHandler::__invoke
 */
final class SetRolesPermissionCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                      $commandBus;
    private ObjectRepository|FieldRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccessWithRemove(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));

        $command = new SetRolesPermissionCommand($field->getId(), FieldPermissionEnum::ReadOnly, [
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));
    }

    public function testSuccessWithKeep(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));

        $command = new SetRolesPermissionCommand($field->getId(), FieldPermissionEnum::ReadOnly, [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));
    }

    public function testSuccessWithReplace(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));

        $command = new SetRolesPermissionCommand($field->getId(), FieldPermissionEnum::ReadAndWrite, [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Anyone));
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Author));
        self::assertSame(FieldPermissionEnum::ReadAndWrite, $this->getPermissionByRole($field->getRolePermissions(), SystemRoleEnum::Responsible));
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set field permissions.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new SetRolesPermissionCommand($field->getId(), FieldPermissionEnum::ReadOnly, [
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        $command = new SetRolesPermissionCommand(self::UNKNOWN_ENTITY_ID, FieldPermissionEnum::ReadOnly, [
            SystemRoleEnum::Responsible,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * Filters lsit of permissions to specified group.
     */
    private function getPermissionByRole(Collection $permissions, SystemRoleEnum $role): ?FieldPermissionEnum
    {
        /** @var FieldRolePermission[] $filtered */
        $filtered = array_filter($permissions->toArray(), fn (FieldRolePermission $permission) => $permission->getRole() === $role);
        $result   = 1 === count($filtered) ? reset($filtered) : null;

        return $result?->getPermission();
    }
}
