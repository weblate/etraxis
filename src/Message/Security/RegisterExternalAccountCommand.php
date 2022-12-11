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

namespace App\Message\Security;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Registers new external account, or updates it if already exists.
 */
final class RegisterExternalAccountCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: User::MAX_EMAIL)]
        #[Assert\Email]
        private readonly string $email,
        #[Assert\NotBlank]
        #[Assert\Length(max: User::MAX_FULLNAME)]
        private readonly string $fullname,
        private readonly AccountProviderEnum $provider,
        #[Assert\NotBlank]
        private readonly string $uid
    ) {
        if (AccountProviderEnum::eTraxis === $provider) {
            throw new \UnexpectedValueException('Invalid account provider: '.$provider->value);
        }
    }

    /**
     * @return string Email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string Full name
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * @return AccountProviderEnum Account provider
     */
    public function getProvider(): AccountProviderEnum
    {
        return $this->provider;
    }

    /**
     * @return string Account UID as in the external provider's system
     */
    public function getUid(): string
    {
        return $this->uid;
    }
}
