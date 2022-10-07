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

use App\Entity\TemplateRolePermission;
use App\Message\Templates\SetRolesPermissionCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\Security\Voter\TemplateVoter;
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
        private readonly TemplateRepositoryInterface $repository,
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
        /** @var null|\App\Entity\Template $template */
        $template = $this->repository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(TemplateVoter::SET_TEMPLATE_PERMISSIONS, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to set template permissions.');
        }

        // Remove all roles which are supposed to not be granted with specified permission, but they currently are.
        /** @var TemplateRolePermission[] $permissions */
        $permissions = array_filter(
            $template->getRolePermissions()->toArray(),
            fn (TemplateRolePermission $permission) => $permission->getPermission() === $command->getPermission()
        );

        foreach ($permissions as $permission) {
            if (!in_array($permission->getRole(), $command->getRoles(), true)) {
                $this->manager->remove($permission);
            }
        }

        // Add all roles which are supposed to be granted with specified permission, but they currently are not.
        $existingRoles = array_map(fn (TemplateRolePermission $permission) => $permission->getRole(), $permissions);

        foreach ($command->getRoles() as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $permission = new TemplateRolePermission($template, $role, $command->getPermission());
                $this->manager->persist($permission);
            }
        }
    }
}
