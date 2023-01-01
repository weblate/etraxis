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

namespace App\Message\Projects;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Projects\CreateProjectCommand
 */
final class CreateProjectCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getName
     * @covers ::isSuspended
     */
    public function testConstructor(): void
    {
        $command = new CreateProjectCommand('eTraxis', null, true);

        self::assertSame('eTraxis', $command->getName());
        self::assertNull($command->getDescription());
        self::assertTrue($command->isSuspended());
    }
}
