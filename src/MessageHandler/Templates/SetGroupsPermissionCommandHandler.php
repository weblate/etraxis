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

use App\Entity\Group;
use App\Entity\TemplateGroupPermission;
use App\Message\Templates\SetGroupsPermissionCommand;
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
final class SetGroupsPermissionCommandHandler implements CommandHandlerInterface
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
    public function __invoke(SetGroupsPermissionCommand $command): void
    {
        /** @var null|\App\Entity\Template $template */
        $template = $this->repository->find($command->getTemplate());

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(TemplateVoter::SET_TEMPLATE_PERMISSIONS, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to set template permissions.');
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
        /** @var TemplateGroupPermission[] $permissions */
        $permissions = array_filter(
            $template->getGroupPermissions()->toArray(),
            fn (TemplateGroupPermission $permission) => $permission->getPermission() === $command->getPermission()
        );

        foreach ($permissions as $permission) {
            if (!in_array($permission->getGroup(), $requestedGroups, true)) {
                $this->manager->remove($permission);
            }
        }

        // Add all groups which are supposed to be granted with specified permission, but they currently are not.
        $existingGroups = array_map(fn (TemplateGroupPermission $permission) => $permission->getGroup(), $permissions);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $permission = new TemplateGroupPermission($template, $group, $command->getPermission());
                $this->manager->persist($permission);
            }
        }
    }
}
