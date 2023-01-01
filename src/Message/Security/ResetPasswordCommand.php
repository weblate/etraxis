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

namespace App\Message\Security;

use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Resets password for specified account.
 */
final class ResetPasswordCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex('/^[a-z0-9]{32}$/i')]
        #[Groups('api')]
        private readonly string $token,
        #[Assert\NotBlank]
        #[Groups('api')]
        private readonly string $password
    ) {
    }

    /**
     * @return string Token for password reset
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string New password (raw)
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
