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
use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new state.
 */
final class CreateStateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Groups('api')]
        private readonly int $template,
        #[Assert\NotBlank]
        #[Assert\Length(max: State::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Groups('api')]
        private readonly StateTypeEnum $type,
        #[Groups('api')]
        private readonly StateResponsibleEnum $responsible
    ) {
    }

    /**
     * @return int ID of the state's template
     */
    public function getTemplate(): int
    {
        return $this->template;
    }

    /**
     * @return string State name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return StateTypeEnum Type of the state
     */
    public function getType(): StateTypeEnum
    {
        return $this->type;
    }

    /**
     * @return StateResponsibleEnum Type of responsibility management
     */
    public function getResponsible(): StateResponsibleEnum
    {
        return $this->responsible;
    }
}
