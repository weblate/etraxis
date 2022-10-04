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
 * @coversDefaultClass \App\Message\UserSettings\UpdateProfileCommand
 */
final class UpdateProfileCommandTest extends TestCase
{
    /**
     * @covers ::getEmail
     * @covers ::getFullname
     */
    public function testConstructor(): void
    {
        $command = new UpdateProfileCommand('artem@example.com', 'Artem Rodygin');

        self::assertSame('artem@example.com', $command->getEmail());
        self::assertSame('Artem Rodygin', $command->getFullname());
    }
}
