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
 * Field types.
 */
enum FieldTypeEnum: string
{
    case Checkbox = 'checkbox';
    case Date     = 'date';
    case Decimal  = 'decimal';
    case Duration = 'duration';
    case Issue    = 'issue';
    case List     = 'list';
    case Number   = 'number';
    case String   = 'string';
    case Text     = 'text';
}
