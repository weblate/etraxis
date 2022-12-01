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

use App\Entity\User;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Marks password of specified eTraxis account as forgotten.
 */
final class ForgotPasswordCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: User::MAX_EMAIL)]
        #[Assert\Email]
        #[Groups('api')]
        private readonly string $email
    ) {
    }

    /**
     * @return string Email address of the account
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
