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

namespace App\Message\Groups;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of groups.
 */
final class GetGroupsQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const GROUP_ID          = 'id';
    public const GROUP_PROJECT     = 'project';
    public const GROUP_NAME        = 'name';
    public const GROUP_DESCRIPTION = 'description';
    public const GROUP_IS_GLOBAL   = 'global';
}
