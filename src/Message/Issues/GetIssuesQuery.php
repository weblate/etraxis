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
    public const ISSUE_PROJECT_NAME     = 'projectName';
    public const ISSUE_TEMPLATE         = 'template';
    public const ISSUE_TEMPLATE_NAME    = 'templateName';
    public const ISSUE_STATE            = 'state';
    public const ISSUE_STATE_NAME       = 'stateName';
    public const ISSUE_AUTHOR           = 'author';
    public const ISSUE_AUTHOR_NAME      = 'authorName';
    public const ISSUE_RESPONSIBLE      = 'responsible';
    public const ISSUE_RESPONSIBLE_NAME = 'responsibleName';
    public const ISSUE_CREATED_AT       = 'createdAt';
    public const ISSUE_CHANGED_AT       = 'changedAt';
    public const ISSUE_CLOSED_AT        = 'closedAt';
    public const ISSUE_IS_CLONED        = 'cloned';
    public const ISSUE_IS_CRITICAL      = 'critical';
    public const ISSUE_IS_SUSPENDED     = 'suspended';
    public const ISSUE_IS_CLOSED        = 'closed';
    public const ISSUE_AGE              = 'age';

    /**
     * Removes the limit for number of items to return.
     */
    public function clearLimit(): void
    {
        $this->limit = 0;
    }
}
