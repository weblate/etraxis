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

namespace App\Entity\Enums;

/**
 * UI themes.
 */
enum ThemeEnum: string
{
    case Azure   = 'azure';
    case Emerald = 'emerald';
    case Mars    = 'mars';

    public const FALLBACK = self::Azure;
}
