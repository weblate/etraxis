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

namespace App\Message\UserSettings;

use App\Entity\Enums\LocaleEnum;
use App\Entity\Enums\ThemeEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\UserSettings\UpdateSettingsCommand
 */
final class UpdateSettingsCommandTest extends TestCase
{
    /**
     * @covers ::getLocale
     * @covers ::getTheme
     * @covers ::getTimezone
     * @covers ::isDarkMode
     */
    public function testConstructor(): void
    {
        $command = new UpdateSettingsCommand(LocaleEnum::Russian, ThemeEnum::Emerald, true, 'Pacific/Auckland');

        self::assertSame(LocaleEnum::Russian, $command->getLocale());
        self::assertSame(ThemeEnum::Emerald, $command->getTheme());
        self::assertTrue($command->isDarkMode());
        self::assertSame('Pacific/Auckland', $command->getTimezone());
    }
}
