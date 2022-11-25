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

namespace App\Message\Comments;

use App\Entity\Comment;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Adds new comment to specified issue.
 */
final class AddCommentCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        #[Assert\NotBlank]
        #[Assert\Length(max: Comment::MAX_BODY)]
        private readonly string $body,
        private readonly bool $private
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
     * @return string Comment body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return bool Whether the comment is private
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }
}
