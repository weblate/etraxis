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

use App\Entity\Enums\AccountProviderEnum;
use App\Message\Security\RegisterExternalAccountCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LDAP user loader.
 */
class LdapUserLoader
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly LdapInterface $ldap,
        protected readonly CommandBusInterface $commandBus,
        protected readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Loads user from LDAP server.
     *
     * @param string $userIdentifier Email address entered in the login form
     *
     * @return null|UserInterface LDAP user object, if found
     */
    public function __invoke(string $userIdentifier): ?UserInterface
    {
        $dn = $fullname = '';

        if (!$this->ldap->findUser($userIdentifier, $dn, $fullname)) {
            return null;
        }

        $command = new RegisterExternalAccountCommand($userIdentifier, $fullname, AccountProviderEnum::LDAP, $dn);

        return $this->commandBus->handleWithResult($command);
    }
}
