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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * State transition for group.
 */
#[ORM\Entity]
#[ORM\Table(name: 'state_group_transitions')]
class StateGroupTransition
{
    /**
     * State the transition goes from.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'groupTransitions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected State $fromState;

    /**
     * State the transition goes to.
     */
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected State $toState;

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
    public function __construct(State $fromState, State $toState, Group $group)
    {
        if ($fromState->getTemplate() !== $toState->getTemplate()) {
            throw new \UnexpectedValueException('States must belong the same template.');
        }

        if (!$group->isGlobal() && $group->getProject() !== $fromState->getTemplate()->getProject()) {
            throw new \UnexpectedValueException('Unknown group: '.$group->getName());
        }

        $this->fromState = $fromState;
        $this->toState   = $toState;
        $this->group     = $group;
    }

    /**
     * Property getter.
     */
    public function getFromState(): State
    {
        return $this->fromState;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getToState(): State
    {
        return $this->toState;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getGroup(): Group
    {
        return $this->group;
    }
}
