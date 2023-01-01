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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * State responsible group.
 */
#[ORM\Entity]
#[ORM\Table(name: 'state_responsible_groups')]
class StateResponsibleGroup
{
    /**
     * State.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'responsibleGroups')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected State $state;

    /**
     * Group.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Group $group;

    /**
     * Constructor.
     */
    public function __construct(State $state, Group $group)
    {
        if (!$group->isGlobal() && $group->getProject() !== $state->getTemplate()->getProject()) {
            throw new \UnexpectedValueException('Unknown group: '.$group->getName());
        }

        $this->state = $state;
        $this->group = $group;
    }

    /**
     * Property getter.
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Property getter.
     */
    public function getGroup(): Group
    {
        return $this->group;
    }
}
