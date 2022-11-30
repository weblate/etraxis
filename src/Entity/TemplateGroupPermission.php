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

namespace App\Entity;

use App\Entity\Enums\TemplatePermissionEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Template permission for group.
 */
#[ORM\Entity]
#[ORM\Table(name: 'template_group_permissions')]
class TemplateGroupPermission
{
    /**
     * Template.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'groupPermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Template $template;

    /**
     * Group.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Group $group;

    /**
     * Permission granted to the group for this template.
     */
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $permission;

    /**
     * Constructor.
     */
    public function __construct(Template $template, Group $group, TemplatePermissionEnum $permission)
    {
        if (!$group->isGlobal() && $group->getProject() !== $template->getProject()) {
            throw new \UnexpectedValueException('Unknown group: '.$group->getName());
        }

        $this->template   = $template;
        $this->group      = $group;
        $this->permission = $permission->value;
    }

    /**
     * Property getter.
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getPermission(): TemplatePermissionEnum
    {
        return TemplatePermissionEnum::from($this->permission);
    }
}
