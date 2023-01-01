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

use App\Controller\ApiControllerInterface;
use App\Entity\Issue;
use App\MessageBus\Contracts\CommandInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new issue.
 */
final class CreateIssueCommand implements CommandInterface
{
    private int $time;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Groups('api')]
        private readonly int $template,
        #[Assert\NotBlank]
        #[Assert\Length(max: Issue::MAX_SUBJECT)]
        #[Groups('api')]
        private readonly string $subject,
        #[Groups('api')]
        private readonly ?int $responsible,
        #[Groups('api')]
        #[API\Property(type: ApiControllerInterface::TYPE_OBJECT, description: 'Fields values (keys are field IDs).', properties: [
            new API\Property(property: '123', type: ApiControllerInterface::TYPE_BOOLEAN),
            new API\Property(property: '456', type: ApiControllerInterface::TYPE_INTEGER),
            new API\Property(property: '789', type: ApiControllerInterface::TYPE_STRING),
        ])]
        private readonly ?array $fields
    ) {
        $this->time = time();
    }

    /**
     * @return int ID of the template to use
     */
    public function getTemplate(): int
    {
        return $this->template;
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
