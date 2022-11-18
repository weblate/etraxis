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

namespace App\Message\Issues;

use App\Message\AbstractCollectionQuery;

/**
 * Returns a collection of issues.
 */
final class GetIssuesQuery extends AbstractCollectionQuery
{
    // Properties available for filters and order.
    public const ISSUE_ID               = 'id';
    public const ISSUE_SUBJECT          = 'subject';
    public const ISSUE_PROJECT          = 'project';
    public const ISSUE_PROJECT_NAME     = 'project_name';
    public const ISSUE_TEMPLATE         = 'template';
    public const ISSUE_TEMPLATE_NAME    = 'template_name';
    public const ISSUE_STATE            = 'state';
    public const ISSUE_STATE_NAME       = 'state_name';
    public const ISSUE_AUTHOR           = 'author';
    public const ISSUE_AUTHOR_NAME      = 'author_name';
    public const ISSUE_RESPONSIBLE      = 'responsible';
    public const ISSUE_RESPONSIBLE_NAME = 'responsible_name';
    public const ISSUE_CREATED_AT       = 'created_at';
    public const ISSUE_CHANGED_AT       = 'changed_at';
    public const ISSUE_CLOSED_AT        = 'closed_at';
    public const ISSUE_IS_CLONED        = 'is_cloned';
    public const ISSUE_IS_CRITICAL      = 'is_critical';
    public const ISSUE_IS_SUSPENDED     = 'is_suspended';
    public const ISSUE_IS_CLOSED        = 'is_closed';
    public const ISSUE_AGE              = 'age';
}