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
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates profile info of the current user.
 */
final class UpdateSettingsCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly LocaleEnum $locale,
        private readonly ThemeEnum $theme,
        private readonly bool $darkMode,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: 'timezone_identifiers_list')]
        private readonly string $timezone
    ) {
    }

    /**
     * @return LocaleEnum New locale
     */
    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    /**
     * @return ThemeEnum New theme
     */
    public function getTheme(): ThemeEnum
    {
        return $this->theme;
    }

    /**
     * @return bool Theme mode (light/dark)
     */
    public function isDarkMode(): bool
    {
        return $this->darkMode;
    }

    /**
     * @return string New timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
