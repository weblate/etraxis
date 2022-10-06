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

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Groups\CreateGroupCommand
 */
final class CreateGroupCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getName
     * @covers ::getProject
     */
    public function testConstructor(): void
    {
        $command = new CreateGroupCommand(1, 'Members', null);

        self::assertSame(1, $command->getProject());
        self::assertSame('Members', $command->getName());
        self::assertNull($command->getDescription());
    }
}
