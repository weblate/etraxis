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

use App\Entity\Enums\LocaleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Users\UpdateUserCommand
 */
final class UpdateUserCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getEmail
     * @covers ::getFullname
     * @covers ::getLocale
     * @covers ::getTimezone
     * @covers ::getUser
     * @covers ::isAdmin
     * @covers ::isDisabled
     */
    public function testConstructor(): void
    {
        $command = new UpdateUserCommand(1, 'artem@example.com', 'Artem Rodygin', null, true, true, LocaleEnum::Russian, 'Pacific/Auckland');

        self::assertSame(1, $command->getUser());
        self::assertSame('artem@example.com', $command->getEmail());
        self::assertSame('Artem Rodygin', $command->getFullname());
        self::assertNull($command->getDescription());
        self::assertTrue($command->isAdmin());
        self::assertTrue($command->isDisabled());
        self::assertSame(LocaleEnum::Russian, $command->getLocale());
        self::assertSame('Pacific/Auckland', $command->getTimezone());
    }
}
