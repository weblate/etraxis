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
use App\Entity\Field;
use App\Entity\FieldGroupPermission;
use App\Entity\Group;
use App\LoginTrait;
use App\Message\Fields\SetGroupsPermissionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Fields\SetGroupsPermissionCommandHandler::__invoke
 */
final class SetGroupsPermissionCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [/* skipping */ , $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame(FieldPermissionEnum::ReadAndWrite, $this->getPermissionByGroup($field->getGroupPermissions(), $managers->getId()));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByGroup($field->getGroupPermissions(), $developers->getId()));
        self::assertNull($this->getPermissionByGroup($field->getGroupPermissions(), $support->getId()));

        $command = new SetGroupsPermissionCommand($field->getId(), FieldPermissionEnum::ReadOnly, [
            $managers->getId(),
            $support->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByGroup($field->getGroupPermissions(), $managers->getId()));
        self::assertNull($this->getPermissionByGroup($field->getGroupPermissions(), $developers->getId()));
        self::assertSame(FieldPermissionEnum::ReadOnly, $this->getPermissionByGroup($field->getGroupPermissions(), $support->getId()));
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set field permissions.');

        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand($field->getId(), FieldPermissionEnum::ReadAndWrite, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownField(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown field.');

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [/* skipping */ , $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand(self::UNKNOWN_ENTITY_ID, FieldPermissionEnum::ReadAndWrite, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup(): void
    {
        $this->expectException(HandlerFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        $command = new SetGroupsPermissionCommand($field->getId(), FieldPermissionEnum::ReadAndWrite, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * Filters lsit of permissions to specified group.
     */
    private function getPermissionByGroup(Collection $permissions, int $groupId): ?FieldPermissionEnum
    {
        /** @var FieldGroupPermission[] $filtered */
        $filtered = array_filter($permissions->toArray(), fn (FieldGroupPermission $permission) => $permission->getGroup()->getId() === $groupId);
        $result   = 1 === count($filtered) ? reset($filtered) : null;

        return $result?->getPermission();
    }
}
