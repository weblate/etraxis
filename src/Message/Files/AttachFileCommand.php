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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Attaches new file to specified issue.
 */
final class AttachFileCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        #[Assert\File]
        private readonly ?UploadedFile $file
    ) {
    }

    /**
     * @return int Issue ID
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return null|UploadedFile File to attach
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }
}
