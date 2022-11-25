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

namespace App\Message\RelatedIssues;

/**
 * Removes existing related issue from specified issue.
 */
final class RemoveRelatedIssueCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $issue, private readonly int $relatedIssue)
    {
    }

    /**
     * @return int Target issue ID
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return int Related issue ID
     */
    public function getRelatedIssue(): int
    {
        return $this->relatedIssue;
    }
}