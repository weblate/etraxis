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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new state.
 */
final class CreateStateCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $template,
        #[Assert\NotBlank]
        #[Assert\Length(max: State::MAX_NAME)]
        private readonly string $name,
        private readonly StateTypeEnum $type,
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
