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

namespace App\MessageHandler\Security;

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use App\Message\Security\RegisterExternalAccountCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Command handler.
 */
final class RegisterExternalAccountCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly UserRepositoryInterface $repository,
        private readonly string $locale
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(RegisterExternalAccountCommand $command): User
    {
        /** @var User $user */
        $user = $this->repository->findOneByProviderUid($command->getProvider(), $command->getUid());

        // If we can't find the account by its UID, try to find by the email.
        if (null === $user) {
            $this->logger->info('Cannot find by UID.', [
                'provider' => $command->getProvider(),
                'uid'      => $command->getUid(),
            ]);

            $user = $this->repository->findOneByEmail($command->getEmail());
        }

        // Register new account.
        if (null === $user) {
            $this->logger->info('Register external account.', [
                'email'    => $command->getEmail(),
                'fullname' => $command->getFullname(),
            ]);

            $user = new User();

            $user->setLocale(LocaleEnum::from($this->locale));
        }
        // The account already exists - update it.
        else {
            $this->logger->info('Update external account.', [
                'email'    => $command->getEmail(),
                'fullname' => $command->getFullname(),
            ]);
        }

        $user
            ->setEmail($command->getEmail())
            ->setPassword(null)
            ->setFullname($command->getFullname())
            ->setAccountProvider($command->getProvider())
            ->setAccountUid($command->getUid())
        ;

        $this->repository->persist($user);

        return $user;
    }
}
