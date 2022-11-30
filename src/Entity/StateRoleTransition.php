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

use App\Entity\Enums\SystemRoleEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * State transition for system role.
 */
#[ORM\Entity]
#[ORM\Table(name: 'state_role_transitions')]
class StateRoleTransition
{
    /**
     * State the transition goes from.
     */
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'roleTransitions')]
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
     * System role.
     */
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $role;

    /**
     * Constructor.
     */
    public function __construct(State $fromState, State $toState, SystemRoleEnum $role)
    {
        if ($fromState->getTemplate() !== $toState->getTemplate()) {
            throw new \UnexpectedValueException('States must belong the same template.');
        }

        $this->fromState = $fromState;
        $this->toState   = $toState;
        $this->role      = $role->value;
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
    #[Groups('info')]
    #[SerializedName('state')]
    public function getToState(): State
    {
        return $this->toState;
    }

    /**
     * Property getter.
     */
    #[Groups('info')]
    public function getRole(): SystemRoleEnum
    {
        return SystemRoleEnum::from($this->role);
    }
}
