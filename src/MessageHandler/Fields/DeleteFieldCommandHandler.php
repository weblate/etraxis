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

namespace App\MessageHandler\Fields;

use App\Message\Fields\DeleteFieldCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Security\Voter\FieldVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DeleteFieldCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly FieldRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteFieldCommand $command): void
    {
        /** @var null|\App\Entity\Field $field */
        $field = $this->repository->find($command->getField());

        if ($field && !$field->isRemoved()) {
            if (!$this->security->isGranted(FieldVoter::REMOVE_FIELD, $field)) {
                throw new AccessDeniedHttpException('You are not allowed to delete this field.');
            }

            $position = $field->getPosition();
            $fields   = $field->getState()->getFields();

            if ($this->security->isGranted(FieldVoter::DELETE_FIELD, $field)) {
                $this->repository->remove($field);
            } else {
                $field->remove();
                $this->repository->persist($field);
            }

            // Reorder remaining fields.
            foreach ($fields as $field) {
                if ($field->getPosition() > $position) {
                    $field->setPosition($field->getPosition() - 1);
                    $this->repository->persist($field);
                }
            }
        }
    }
}
