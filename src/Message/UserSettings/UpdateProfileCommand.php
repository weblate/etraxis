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

namespace App\Message\UserSettings;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Updates profile info of the current user.
 */
final class UpdateProfileCommand
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
        private readonly string $fullname
    ) {
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
}
