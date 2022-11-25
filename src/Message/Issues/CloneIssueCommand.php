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
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Clones specified issue.
 */
final class CloneIssueCommand implements CommandInterface
{
    private int $time;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        #[Assert\NotBlank]
        #[Assert\Length(max: Issue::MAX_SUBJECT)]
        private readonly string $subject,
        private readonly ?int $responsible,
        private readonly ?array $fields
    ) {
        $this->time = time();
    }

    /**
     * @return int ID of the original issue
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return string Issue subject
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return null|int ID of user to assign the issue to (ignored when not applicable)
     */
    public function getResponsible(): ?int
    {
        return $this->responsible;
    }

    /**
     * @return int Unix Epoch timestamp when the issue is being created
     */
    public function getTime(): int
    {
        return $this->time;
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
