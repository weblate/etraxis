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

/**
 * Creates new group.
 */
final class CreateGroupCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly ?int $project, private readonly string $name, private readonly ?string $description)
    {
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
