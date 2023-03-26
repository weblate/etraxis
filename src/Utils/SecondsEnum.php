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

namespace App\Utils;

/**
 * Time constants expressed in seconds.
 */
enum SecondsEnum: int
{
    case OneDay    = 86400;
    case TwoHours  = 7200;
    case OneMinute = 60;
}
