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

namespace App\MessageHandler\UserSettings;

use App\Message\UserSettings\UpdateProfileCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
final class UpdateProfileCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    public function __invoke(UpdateProfileCommand $command): void
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedHttpException('User must be logged in.');
        }

        /** @var \App\Entity\User $user */
        $user = $token->getUser();

        if ($user->isAccountExternal()) {
            throw new AccessDeniedHttpException('Not applicable for external accounts.');
        }

        $user
            ->setEmail($command->getEmail())
            ->setFullname($command->getFullname())
        ;

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as logins, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);
    }
}
