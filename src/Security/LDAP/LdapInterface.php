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

namespace App\Security\LDAP;

/**
 * Interface to the LDAP service.
 */
interface LdapInterface
{
    /**
     * Finds LDAP account by its email address.
     *
     * @param string $email    Email address
     * @param string $dn       Distinguished name of the account, if found
     * @param string $fullname Full name of the account, if found
     *
     * @return bool Whether the account was found
     */
    public function findUser(string $email, string &$dn, string &$fullname): bool;

    /**
     * Checks specified credentials.
     *
     * @param string $dn       Distinguished name of the account
     * @param string $password LDAP password
     *
     * @return bool Whether the password is valid
     */
    public function checkCredentials(string $dn, string $password): bool;
}
