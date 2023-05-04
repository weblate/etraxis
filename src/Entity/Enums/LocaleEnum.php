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

namespace App\Entity\Enums;

/**
 * Locales.
 */
enum LocaleEnum: string
{
    case Bulgarian        = 'bg';
    case Czech            = 'cs';
    case German           = 'de';
    case English          = 'en';
    case Spanish          = 'es';
    case French           = 'fr';
    case Hungarian        = 'hu';
    case Italian          = 'it';
    case Japanese         = 'ja';
    case Latvian          = 'lv';
    case Dutch            = 'nl';
    case Polish           = 'pl';
    case PortugueseBrazil = 'pt_BR';
    case Romanian         = 'ro';
    case Russian          = 'ru';
    case Swedish          = 'sv';
    case Turkish          = 'tr';

    public const FALLBACK = self::English;
}
