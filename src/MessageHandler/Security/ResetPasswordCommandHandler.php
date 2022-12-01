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

use App\Message\Security\ResetPasswordCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Command handler.
 */
final class ResetPasswordCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ResetPasswordCommand $command): void
    {
        $user = $this->repository->findOneByResetToken($command->getToken());

        if (!$user || !$user->isResetTokenValid($command->getToken())) {
            throw new NotFoundHttpException('Unknown user.');
        }

        try {
            $password = $this->hasher->hashPassword($user, $command->getPassword());

            $user->setPassword($password);
            $user->clearResetToken();
        } catch (InvalidPasswordException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->repository->persist($user);
    }
}
