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
use App\MessageBus\Contracts\CommandInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Changes state of the issue to the specified one.
 */
final class ChangeStateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $issue,
        private readonly int $state,
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
    }

    /**
     * @return int Issue ID
     */
    public function getIssue(): int
    {
        return $this->issue;
    }

    /**
     * @return int New state ID
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return null|int ID of user to assign the issue to (ignored when not applicable)
     */
    public function getResponsible(): ?int
    {
        return $this->responsible;
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
