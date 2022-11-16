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

namespace App\Message\Templates;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of templates.
 */
final class GetTemplatesQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const TEMPLATE_ID           = 'id';
    public const TEMPLATE_PROJECT      = 'project';
    public const TEMPLATE_NAME         = 'name';
    public const TEMPLATE_PREFIX       = 'prefix';
    public const TEMPLATE_DESCRIPTION  = 'description';
    public const TEMPLATE_CRITICAL_AGE = 'critical_age';
    public const TEMPLATE_FROZEN_TIME  = 'frozen_time';
    public const TEMPLATE_IS_LOCKED    = 'is_locked';
}
