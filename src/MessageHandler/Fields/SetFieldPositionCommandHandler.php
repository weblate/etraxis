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

namespace App\MessageHandler\Fields;

use App\Entity\Field;
use App\Message\Fields\SetFieldPositionCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Security\Voter\FieldVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SetFieldPositionCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly FieldRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetFieldPositionCommand $command): void
    {
        /** @var null|Field $field */
        $field = $this->repository->find($command->getField());

        if (!$field || $field->isRemoved()) {
            throw new NotFoundHttpException('Unknown field.');
        }

        if (!$this->security->isGranted(FieldVoter::UPDATE_FIELD, $field)) {
            throw new AccessDeniedHttpException('You are not allowed to update this field.');
        }

        $fields = $field->getState()->getFields();

        $count = count($fields);

        $oldPosition = $field->getPosition();
        $newPosition = min($command->getPosition(), $count);

        $this->setPosition($field, 0);

        if ($oldPosition < $newPosition) {
            // Moving the field down.
            for ($i = $oldPosition; $i < $newPosition; $i++) {
                $this->setPosition($fields[$i], $i);
            }
        } elseif ($oldPosition > $newPosition) {
            // Moving the field up.
            for ($i = $oldPosition; $i > $newPosition; $i--) {
                $this->setPosition($fields[$i - 2], $i);
            }
        }

        $this->setPosition($field, $newPosition);
    }

    /**
     * Sets new position for specified field.
     */
    private function setPosition(Field $field, int $position): void
    {
        $query = $this->manager->createQuery('
            UPDATE App:Field f
            SET f.position = :position
            WHERE f.id = :field
        ');

        $query->execute([
            'field'    => $field->getId(),
            'position' => $position,
        ]);
    }
}
