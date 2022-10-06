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

namespace App\Message\Users;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of users.
 */
final class GetUsersQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const USER_ID          = 'id';
    public const USER_EMAIL       = 'email';
    public const USER_FULLNAME    = 'fullname';
    public const USER_DESCRIPTION = 'description';
    public const USER_ADMIN       = 'admin';
    public const USER_DISABLED    = 'disabled';
    public const USER_PROVIDER    = 'provider';
}
