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

namespace App\Security\Voter;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Issue;
use App\Entity\Template;
use App\Entity\TemplateGroupPermission;
use App\Entity\TemplateRolePermission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait with ability to check user permissions.
 */
trait PermissionsTrait
{
    protected array $rolesCache  = [];
    protected array $groupsCache = [];

    /**
     * Checks whether the specified system role is granted to specified permission for the template.
     *
     * @param EntityManagerInterface $manager    Entity manager
     * @param Template               $template   Template
     * @param SystemRoleEnum         $role       System role
     * @param TemplatePermissionEnum $permission Permission
     */
    protected function hasRolePermission(EntityManagerInterface $manager, Template $template, SystemRoleEnum $role, TemplatePermissionEnum $permission): bool
    {
        // If we don't have the info about permissions yet, retrieve it from the DB and cache to reuse.
        if (!array_key_exists($template->getId(), $this->rolesCache)) {
            $query = $manager->createQueryBuilder();

            $query
                ->distinct()
                ->select('tp.role')
                ->addSelect('tp.permission')
                ->from(TemplateRolePermission::class, 'tp')
                ->where('tp.template = :template')
            ;

            $this->rolesCache[$template->getId()] = $query->getQuery()->execute([
                'template' => $template,
            ]);
        }

        return in_array(['role' => $role->value, 'permission' => $permission->value], $this->rolesCache[$template->getId()], true);
    }

    /**
     * Checks whether the specified user is granted to specified group permission for the template.
     *
     * @param EntityManagerInterface $manager    Entity manager
     * @param Template               $template   Template
     * @param User                   $user       User
     * @param TemplatePermissionEnum $permission Permission
     */
    protected function hasGroupPermission(EntityManagerInterface $manager, Template $template, User $user, TemplatePermissionEnum $permission): bool
    {
        $key = sprintf('%s:%s', $template->getId(), $user->getId());

        // If we don't have the info about permissions yet, retrieve it from the DB and cache to reuse.
        if (!array_key_exists($key, $this->groupsCache)) {
            $query = $manager->createQueryBuilder();

            $query
                ->distinct()
                ->select('tp.permission')
                ->from(TemplateGroupPermission::class, 'tp')
                ->where('tp.template = :template')
                ->andWhere($query->expr()->in('tp.group', ':groups'))
            ;

            $this->groupsCache[$key] = $query->getQuery()->execute([
                'template' => $template,
                'groups'   => $user->getGroups(),
            ]);
        }

        return in_array(['permission' => $permission->value], $this->groupsCache[$key], true);
    }

    /**
     * Checks whether the specified user is granted to specified permission for the issue either by group or by role.
     *
     * @param EntityManagerInterface $manager    Entity manager
     * @param Issue                  $issue      Issue
     * @param User                   $user       User
     * @param TemplatePermissionEnum $permission Permission
     */
    protected function hasPermission(EntityManagerInterface $manager, Issue $issue, User $user, TemplatePermissionEnum $permission): bool
    {
        // Check whether the user has required permissions as author.
        if ($issue->getAuthor() === $user && $this->hasRolePermission($manager, $issue->getTemplate(), SystemRoleEnum::Author, $permission)) {
            return true;
        }

        // Check whether the user has required permissions as current responsible.
        if ($issue->getResponsible() === $user && $this->hasRolePermission($manager, $issue->getTemplate(), SystemRoleEnum::Responsible, $permission)) {
            return true;
        }

        return $this->hasRolePermission($manager, $issue->getTemplate(), SystemRoleEnum::Anyone, $permission)
            || $this->hasGroupPermission($manager, $issue->getTemplate(), $user, $permission);
    }
}
