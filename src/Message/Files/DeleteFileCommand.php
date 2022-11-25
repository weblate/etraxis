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

namespace App\Message\Files;

use App\MessageBus\Contracts\CommandInterface;

/**
 * Deletes existing file from specified issue.
 */
final class DeleteFileCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $file)
    {
    }

    /**
     * @return int File ID
     */
    public function getFile(): int
    {
        return $this->file;
    }
}
