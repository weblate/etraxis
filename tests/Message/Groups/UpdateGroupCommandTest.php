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
 * @coversDefaultClass \App\Message\Groups\UpdateGroupCommand
 */
final class UpdateGroupCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getGroup
     * @covers ::getName
     */
    public function testConstructor(): void
    {
        $command = new UpdateGroupCommand(1, 'Members', null);

        self::assertSame(1, $command->getGroup());
        self::assertSame('Members', $command->getName());
        self::assertNull($command->getDescription());
    }
}
