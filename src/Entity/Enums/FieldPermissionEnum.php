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
 * Field permissions.
 */
enum FieldPermissionEnum: string
{
    case ReadOnly     = 'R';
    case ReadAndWrite = 'RW';
}
