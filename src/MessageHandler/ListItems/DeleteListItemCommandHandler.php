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

use App\Message\ListItems\DeleteListItemCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Security\Voter\ListItemVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class DeleteListItemCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ListItemRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(DeleteListItemCommand $command): void
    {
        /** @var null|\App\Entity\ListItem $item */
        $item = $this->repository->find($command->getItem());

        if ($item) {
            if (!$this->security->isGranted(ListItemVoter::DELETE_LISTITEM, $item)) {
                throw new AccessDeniedHttpException('You are not allowed to delete this list item.');
            }

            $this->repository->remove($item);
        }
    }
}
