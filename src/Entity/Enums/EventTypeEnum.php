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

namespace App\Entity\Enums;

/**
 * Event types.
 */
enum EventTypeEnum: string
{
    case IssueCreated        = 'issue.created';
    case IssueEdited         = 'issue.edited';
    case StateChanged        = 'state.changed';
    case IssueReopened       = 'issue.reopened';
    case IssueClosed         = 'issue.closed';
    case IssueAssigned       = 'issue.assigned';
    case IssueReassigned     = 'issue.reassigned';
    case IssueSuspended      = 'issue.suspended';
    case IssueResumed        = 'issue.resumed';
    case PublicComment       = 'comment.public';
    case PrivateComment      = 'comment.private';
    case FileAttached        = 'file.attached';
    case FileDeleted         = 'file.deleted';
    case DependencyAdded     = 'dependency.added';
    case DependencyRemoved   = 'dependency.removed';
    case RelatedIssueAdded   = 'related.added';
    case RelatedIssueRemoved = 'related.removed';
}
