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

namespace App\MessageHandler\ListItems;

use App\Entity\ListItem;
use App\Message\ListItems\CreateListItemCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Security\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class CreateListItemCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly FieldRepositoryInterface $fieldRepository,
        private readonly ListItemRepositoryInterface $itemRepository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(CreateListItemCommand $command): ListItem
    {
        /** @var null|\App\Entity\Field $field */
        $field = $this->fieldRepository->find($command->getField());

        if (!$field) {
            throw new NotFoundHttpException('Unknown field.');
        }

        if (!$this->security->isGranted(ListItemVoter::CREATE_LISTITEM, $field)) {
            throw new AccessDeniedHttpException('You are not allowed to create new list item.');
        }

        $item = new ListItem($field);

        $item
            ->setValue($command->getValue())
            ->setText($command->getText())
        ;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->itemRepository->persist($item);

        return $item;
    }
}
