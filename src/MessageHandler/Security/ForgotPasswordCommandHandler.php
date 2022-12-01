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

namespace App\MessageHandler\Security;

use App\Message\Security\ForgotPasswordCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;

/**
 * Command handler.
 */
final class ForgotPasswordCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly UserRepositoryInterface $repository)
    {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(ForgotPasswordCommand $command): void
    {
        $user = $this->repository->findOneByEmail($command->getEmail());

        if (!$user || $user->isAccountExternal()) {
            return;
        }

        // Token expires in 2 hours.
        $user->generateResetToken(new \DateInterval('PT2H'));

        $this->repository->persist($user);
    }
}
