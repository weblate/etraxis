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

use App\Entity\Enums\AccountProviderEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Security\RegisterExternalAccountCommand
 */
final class RegisterExternalAccountCommandTest extends TestCase
{
    /**
     * @covers ::getEmail
     * @covers ::getFullname
     * @covers ::getProvider
     * @covers ::getUid
     */
    public function testConstructor(): void
    {
        $command = new RegisterExternalAccountCommand('anna@example.com', 'Anna Rodygina', AccountProviderEnum::LDAP, '123');

        self::assertSame('anna@example.com', $command->getEmail());
        self::assertSame('Anna Rodygina', $command->getFullname());
        self::assertSame(AccountProviderEnum::LDAP, $command->getProvider());
        self::assertSame('123', $command->getUid());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorInvalidProvider(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid account provider: etraxis');

        new RegisterExternalAccountCommand('anna@example.com', 'Anna Rodygina', AccountProviderEnum::eTraxis, '123');
    }
}
