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

namespace App\Message\Files;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Files\AttachFileCommand
 */
final class AttachFileCommandTest extends TestCase
{
    /**
     * @covers ::getFile
     * @covers ::getIssue
     */
    public function testConstructor(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $command = new AttachFileCommand(1, $file);

        self::assertSame(1, $command->getIssue());
        self::assertSame($file, $command->getFile());
    }
}
