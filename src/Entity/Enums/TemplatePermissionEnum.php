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
 * Template permissions.
 */
enum TemplatePermissionEnum: string
{
    case ViewIssues          = 'issue.view';
    case CreateIssues        = 'issue.create';
    case EditIssues          = 'issue.edit';
    case ReassignIssues      = 'issue.reassign';
    case SuspendIssues       = 'issue.suspend';
    case ResumeIssues        = 'issue.resume';
    case DeleteIssues        = 'issue.delete';
    case AddComments         = 'comment.add';
    case PrivateComments     = 'comment.private';
    case AttachFiles         = 'file.attach';
    case DeleteFiles         = 'file.delete';
    case ManageDependencies  = 'manage.dependencies';
    case ManageRelatedIssues = 'manage.related';
}
