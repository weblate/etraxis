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

namespace App\Message\UserSettings;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\UserSettings\SetPasswordCommand
 */
final class SetPasswordCommandTest extends TestCase
{
    /**
     * @covers ::getPassword
     * @covers ::getUser
     */
    public function testConstructor(): void
    {
        $command = new SetPasswordCommand(1, 'secret');

        self::assertSame(1, $command->getUser());
        self::assertSame('secret', $command->getPassword());
    }
}
