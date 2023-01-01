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
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Security\ResetPasswordCommand
 */
final class ResetPasswordCommandTest extends TestCase
{
    /**
     * @covers ::getPassword
     * @covers ::getToken
     */
    public function testConstructor(): void
    {
        $token = str_replace('-', '', Uuid::v4()->toRfc4122());

        $command = new ResetPasswordCommand($token, 'secret');

        self::assertSame($token, $command->getToken());
        self::assertSame('secret', $command->getPassword());
    }
}
