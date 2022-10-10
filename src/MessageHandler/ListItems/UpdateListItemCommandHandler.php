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

namespace App\MessageHandler\ListItems;

use App\Message\ListItems\UpdateListItemCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Security\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class UpdateListItemCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly ListItemRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(UpdateListItemCommand $command): void
    {
        /** @var null|\App\Entity\ListItem $item */
        $item = $this->repository->find($command->getItem());

        if (!$item) {
            throw new NotFoundHttpException('Unknown list item.');
        }

        if (!$this->security->isGranted(ListItemVoter::UPDATE_LISTITEM, $item)) {
            throw new AccessDeniedHttpException('You are not allowed to update this list item.');
        }

        $item
            ->setValue($command->getValue())
            ->setText($command->getText())
        ;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($item);
    }
}
