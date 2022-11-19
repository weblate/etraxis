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

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Files\DeleteFileCommand
 */
final class DeleteFileCommandTest extends TestCase
{
    /**
     * @covers ::getFile
     */
    public function testConstructor(): void
    {
        $command = new DeleteFileCommand(1);

        self::assertSame(1, $command->getFile());
    }
}
