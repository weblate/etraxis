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

namespace App\MessageHandler\Fields;

use App\Entity\FieldRolePermission;
use App\Message\Fields\SetRolesPermissionCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Security\Voter\FieldVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SetRolesPermissionCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly FieldRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetRolesPermissionCommand $command): void
    {
        /** @var null|\App\Entity\Field $field */
        $field = $this->repository->find($command->getField());

        if (!$field) {
            throw new NotFoundHttpException('Unknown field.');
        }

        if (!$this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $field)) {
            throw new AccessDeniedHttpException('You are not allowed to set field permissions.');
        }

        // Remove all roles which are supposed to not be granted with specified permission, but they currently are.
        /** @var FieldRolePermission[] $permissions */
        $permissions = array_filter(
            $field->getRolePermissions()->toArray(),
            fn (FieldRolePermission $permission) => $permission->getPermission() === $command->getPermission()
        );

        foreach ($permissions as $permission) {
            if (!in_array($permission->getRole(), $command->getRoles(), true)) {
                $this->manager->remove($permission);
            }
        }

        // Update all roles which are supposed to be granted with specified permission, but they currently are granted with another permission.
        foreach ($field->getRolePermissions() as $permission) {
            if (in_array($permission->getRole(), $command->getRoles(), true) && $permission->getPermission() !== $command->getPermission()) {
                $permission->setPermission($command->getPermission());
                $this->manager->persist($permission);
            }
        }

        // Add all roles which are supposed to be granted with specified permission, but they currently are not.
        $existingRoles = array_map(fn (FieldRolePermission $permission) => $permission->getRole(), $field->getRolePermissions()->toArray());

        foreach ($command->getRoles() as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $permission = new FieldRolePermission($field, $role, $command->getPermission());
                $this->manager->persist($permission);
            }
        }
    }
}
