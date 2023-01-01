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

namespace App\Message\Users;

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates specified account.
 */
final class UpdateUserCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly int $user,
        #[Assert\NotBlank]
        #[Assert\Length(max: User::MAX_EMAIL)]
        #[Assert\Email]
        #[Groups('api')]
        private readonly string $email,
        #[Assert\NotBlank]
        #[Assert\Length(max: User::MAX_FULLNAME)]
        #[Groups('api')]
        private readonly string $fullname,
        #[Assert\Length(max: User::MAX_DESCRIPTION)]
        #[Groups('api')]
        private readonly ?string $description,
        #[Groups('api')]
        private readonly bool $admin,
        #[Groups('api')]
        private readonly bool $disabled,
        #[Groups('api')]
        private readonly LocaleEnum $locale,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: ['DateTimeZone', 'listIdentifiers'])]
        #[Groups('api')]
        private readonly string $timezone
    ) {
    }

    /**
     * @return int User ID
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return string New email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string New full name
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * @return null|string New description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool New role (whether they have administrator permissions)
     */
    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @return bool New status
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return LocaleEnum New locale
     */
    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    /**
     * @return string New timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
