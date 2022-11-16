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

namespace App\Message\States;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of states.
 */
final class GetStatesQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const STATE_ID          = 'id';
    public const STATE_PROJECT     = 'project';
    public const STATE_TEMPLATE    = 'template';
    public const STATE_NAME        = 'name';
    public const STATE_TYPE        = 'type';
    public const STATE_RESPONSIBLE = 'responsible';
}
