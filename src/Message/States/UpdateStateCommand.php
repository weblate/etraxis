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

namespace App\Message\States;

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\State;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified state.
 */
final class UpdateStateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $state,
        #[Assert\NotBlank]
        #[Assert\Length(max: State::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Groups('api')]
        private readonly StateResponsibleEnum $responsible
    ) {
    }

    /**
     * @return int State ID
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return string New state name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return StateResponsibleEnum New type of responsibility management
     */
    public function getResponsible(): StateResponsibleEnum
    {
        return $this->responsible;
    }
}
