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
 * State responsibility values.
 */
enum StateResponsibleEnum: string
{
    case Keep   = 'keep';
    case Assign = 'assign';
    case Remove = 'remove';
}
