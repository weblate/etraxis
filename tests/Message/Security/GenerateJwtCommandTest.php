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

namespace App\Message\Security;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Security\GenerateJwtCommand
 */
final class GenerateJwtCommandTest extends TestCase
{
    /**
     * @covers ::getEmail
     * @covers ::getPassword
     */
    public function testConstructor(): void
    {
        $command = new GenerateJwtCommand('artem@example.com', 'secret');

        self::assertSame('artem@example.com', $command->getEmail());
        self::assertSame('secret', $command->getPassword());
    }
}
