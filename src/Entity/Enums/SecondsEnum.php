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
 * Time constants expressed in seconds.
 */
enum SecondsEnum: int
{
    case OneDay    = 86400;
    case OneMinute = 60;
}
