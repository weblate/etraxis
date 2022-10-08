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

namespace App\Message\Fields;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of fields.
 */
final class GetFieldsQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const FIELD_ID          = 'id';
    public const FIELD_PROJECT     = 'project';
    public const FIELD_TEMPLATE    = 'template';
    public const FIELD_STATE       = 'state';
    public const FIELD_NAME        = 'name';
    public const FIELD_TYPE        = 'type';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_POSITION    = 'position';
    public const FIELD_REQUIRED    = 'required';
}
