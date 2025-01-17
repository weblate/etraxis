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

namespace App\Controller;

use App\Utils\OpenApiInterface;

/**
 * Marker interface for API controllers.
 */
interface ApiControllerInterface extends OpenApiInterface
{
    // Parameter placement.
    public const PARAMETER_QUERY  = 'query';
    public const PARAMETER_HEADER = 'header';
    public const PARAMETER_PATH   = 'path';
    public const PARAMETER_COOKIE = 'cookie';

    // Query attributes.
    public const QUERY_OFFSET  = 'offset';
    public const QUERY_LIMIT   = 'limit';
    public const QUERY_SEARCH  = 'search';
    public const QUERY_FILTERS = 'filters';
    public const QUERY_ORDER   = 'order';

    // Collection attributes.
    public const COLLECTION_TOTAL = 'total';
    public const COLLECTION_ITEMS = 'items';
}
