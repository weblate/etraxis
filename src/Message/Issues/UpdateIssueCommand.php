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

use App\Entity\Issue;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified issue.
 */
final class UpdateIssueCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: Issue::MAX_SUBJECT)]
        private readonly ?string $subject,
        private readonly ?array $fields
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
     * @return null|string Issue subject
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return null|array Fields values (keys are field IDs)
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @param int $id Field ID
     *
     * @return null|mixed Field value
     */
    public function getField(int $id): mixed
    {
        return null !== $this->fields
            ? $this->fields[$id] ?? null
            : null;
    }
}
