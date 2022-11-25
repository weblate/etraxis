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
 * Marks specified issues as unread.
 */
final class MarkAsUnreadCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Regex('/^\d+$/'),
        ])]
        private readonly array $issues
    ) {
    }

    /**
     * @return array Issue IDs
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
