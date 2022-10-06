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

namespace App\Message\Users;

use App\Entity\Enums\LocaleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Users\CreateUserCommand
 */
final class CreateUserCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getEmail
     * @covers ::getFullname
     * @covers ::getLocale
     * @covers ::getPassword
     * @covers ::getTimezone
     * @covers ::isAdmin
     * @covers ::isDisabled
     */
    public function testConstructor(): void
    {
        $command = new CreateUserCommand('artem@example.com', 'secret', 'Artem Rodygin', null, true, true, LocaleEnum::Russian, 'Pacific/Auckland');

        self::assertSame('artem@example.com', $command->getEmail());
        self::assertSame('secret', $command->getPassword());
        self::assertSame('Artem Rodygin', $command->getFullname());
        self::assertNull($command->getDescription());
        self::assertTrue($command->isAdmin());
        self::assertTrue($command->isDisabled());
        self::assertSame(LocaleEnum::Russian, $command->getLocale());
        self::assertSame('Pacific/Auckland', $command->getTimezone());
    }
}
