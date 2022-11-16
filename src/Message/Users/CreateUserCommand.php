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

namespace App\Message\Users;

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creates new account.
 */
final class CreateUserCommand
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\Length(max: User::MAX_EMAIL)]
        #[Assert\Email]
        private readonly string $email,
        private readonly string $password,
        #[Assert\Length(max: User::MAX_FULLNAME)]
        private readonly string $fullname,
        #[Assert\Length(max: User::MAX_DESCRIPTION)]
        private readonly ?string $description,
        private readonly bool $admin,
        private readonly bool $disabled,
        private readonly LocaleEnum $locale,
        #[Assert\Choice(callback: 'timezone_identifiers_list', strict: true)]
        private readonly string $timezone
    ) {
    }

    /**
     * @return string Email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string Password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string Full name
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * @return null|string Description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool Role (whether they have administrator permissions)
     */
    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @return bool Status
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return LocaleEnum Locale
     */
    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    /**
     * @return string Timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
