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

namespace App\Message\Security;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Security\ForgotPasswordCommand
 */
final class ForgotPasswordCommandTest extends TestCase
{
    /**
     * @covers ::getEmail
     */
    public function testConstructor(): void
    {
        $command = new ForgotPasswordCommand('anna@example.com');

        self::assertSame('anna@example.com', $command->getEmail());
    }
}
