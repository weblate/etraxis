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

namespace App\Message\Users;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Users\EnableUserCommand
 */
final class EnableUserCommandTest extends TestCase
{
    /**
     * @covers ::getUser
     */
    public function testConstructor(): void
    {
        $command = new EnableUserCommand(1);

        self::assertSame(1, $command->getUser());
    }
}
