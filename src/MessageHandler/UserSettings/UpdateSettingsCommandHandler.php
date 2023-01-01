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

use App\Message\UserSettings\UpdateSettingsCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
final class UpdateSettingsCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     */
    public function __invoke(UpdateSettingsCommand $command): void
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedHttpException('User must be logged in.');
        }

        /** @var \App\Entity\User $user */
        $user = $token->getUser();

        $user
            ->setLocale($command->getLocale())
            ->setTheme($command->getTheme())
            ->setTimezone($command->getTimezone())
        ;

        $this->repository->persist($user);

        $this->requestStack->getSession()->set('_locale', $user->getLocale()->value);
    }
}
