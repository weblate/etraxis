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

namespace App\Message\Groups;

use App\Entity\Group;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new group.
 */
final class CreateGroupCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Groups('api')]
        private readonly ?int $project,
        #[Assert\NotBlank]
        #[Assert\Length(max: Group::MAX_NAME)]
        #[Groups('api')]
        private readonly string $name,
        #[Assert\Length(max: Group::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description
    ) {
    }

    /**
     * @return null|int ID of the group's project (empty for global group)
     */
    public function getProject(): ?int
    {
        return $this->project;
    }

    /**
     * @return string Group name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string Description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
