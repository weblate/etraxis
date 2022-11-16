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

namespace App\Message\Groups;

use App\Entity\Group;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified group.
 */
final class UpdateGroupCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $group,
        #[Assert\Length(max: Group::MAX_NAME)]
        private readonly string $name,
        #[Assert\Length(max: Group::MAX_DESCRIPTION)]
        private readonly ?string $description
    ) {
    }

    /**
     * @return int Group ID
     */
    public function getGroup(): int
    {
        return $this->group;
    }

    /**
     * @return string New name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string New description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
