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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Suspends specified issue.
 */
final class SuspendIssueCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        #[Assert\NotBlank]
        #[Assert\Regex('/^\d{4}\-[0-1]\d\-[0-3]\d$/')]
        private readonly string $date
    ) {
    }

    /**
     * @return int Issue ID
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return string The issue is being suspended until midnight of this date (YYYY-MM-DD)
     */
    public function getDate(): string
    {
        return $this->date;
    }
}
