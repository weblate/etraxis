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

use App\Entity\FieldGroupPermission;
use App\Entity\Group;
use App\Message\Fields\SetGroupsPermissionCommand;
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
final class SetGroupsPermissionCommandHandler implements CommandHandlerInterface
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
    public function __invoke(SetGroupsPermissionCommand $command): void
    {
        /** @var null|\App\Entity\Field $field */
        $field = $this->repository->find($command->getField());

        if (!$field) {
            throw new NotFoundHttpException('Unknown field.');
        }

        if (!$this->security->isGranted(FieldVoter::SET_FIELD_PERMISSIONS, $field)) {
            throw new AccessDeniedHttpException('You are not allowed to set field permissions.');
        }

        // Retrieve all groups specified in the command.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
        ;

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->getGroups(),
        ]);

        // Remove all groups which are supposed to not be granted with specified permission, but they currently are.
        /** @var FieldGroupPermission[] $permissions */
        $permissions = array_filter(
            $field->getGroupPermissions()->toArray(),
            fn (FieldGroupPermission $permission) => $permission->getPermission() === $command->getPermission()
        );

        foreach ($permissions as $permission) {
            if (!in_array($permission->getGroup(), $requestedGroups, true)) {
                $this->manager->remove($permission);
            }
        }

        // Update all groups which are supposed to be granted with specified permission, but they currently are granted with another permission.
        foreach ($field->getGroupPermissions() as $permission) {
            if (in_array($permission->getGroup()->getId(), $command->getGroups(), true) && $permission->getPermission() !== $command->getPermission()) {
                $permission->setPermission($command->getPermission());
                $this->manager->persist($permission);
            }
        }

        // Add all groups which are supposed to be granted with specified permission, but they currently are not.
        $existingGroups = array_map(fn (FieldGroupPermission $permission) => $permission->getGroup(), $field->getGroupPermissions()->toArray());

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $permission = new FieldGroupPermission($field, $group, $command->getPermission());
                $this->manager->persist($permission);
            }
        }
    }
}
