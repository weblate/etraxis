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

namespace App\Message\Projects;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of projects.
 */
final class GetProjectsQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const PROJECT_ID           = 'id';
    public const PROJECT_NAME         = 'name';
    public const PROJECT_DESCRIPTION  = 'description';
    public const PROJECT_CREATED_AT   = 'createdAt';
    public const PROJECT_IS_SUSPENDED = 'suspended';
}
