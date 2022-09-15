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
 * State types.
 */
enum StateTypeEnum: string
{
    case Initial      = 'initial';
    case Intermediate = 'intermediate';
    case Final        = 'final';
}
