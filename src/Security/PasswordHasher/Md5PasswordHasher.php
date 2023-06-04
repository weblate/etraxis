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

namespace App\Security\PasswordHasher;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * eTraxis legacy password hasher.
 *
 * Up to version 3.6.7 passwords were stored as MD5 hashes.
 * For backward compatibility we let user authenticate if his password is stored in a legacy way.
 */
class Md5PasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    /**
     * @see PasswordHasherInterface::hash
     */
    public function hash(string $plainPassword): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return md5($plainPassword);
    }

    /**
     * @see PasswordHasherInterface::verify
     */
    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return !$this->isPasswordTooLong($plainPassword) && $hashedPassword === $this->hash($plainPassword);
    }

    /**
     * @see PasswordHasherInterface::needsRehash
     */
    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }
}
