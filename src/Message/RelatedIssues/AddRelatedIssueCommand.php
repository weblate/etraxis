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

namespace App\Message\RelatedIssues;

use App\MessageBus\Contracts\CommandInterface;

/**
 * Adds new related issue to specified issue.
 */
final class AddRelatedIssueCommand implements CommandInterface
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
