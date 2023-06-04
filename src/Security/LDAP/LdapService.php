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

namespace App\Security\LDAP;

use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Exception\ExceptionInterface;
use Symfony\Component\Ldap\Ldap;

/**
 * LDAP service.
 */
class LdapService implements LdapInterface
{
    protected const SCHEME_NULL  = 'null';
    protected const SCHEME_LDAP  = 'ldap';
    protected const SCHEME_LDAPS = 'ldaps';

    protected const ENCRYPTION_NONE = 'none';
    protected const ENCRYPTION_SSL  = 'ssl';
    protected const ENCRYPTION_TLS  = 'tls';

    protected LoggerInterface $logger;

    protected ?string $username;
    protected ?string $password;
    protected ?string $basedn;

    protected ?\Symfony\Component\Ldap\LdapInterface $ldap = null;

    /**
     * Configures the connection from the specified URL.
     */
    public function __construct(LoggerInterface $logger, ?string $url, ?string $basedn)
    {
        $this->logger = $logger;
        $this->basedn = $basedn;

        $uri = parse_url($url ?? 'null://localhost');

        $scheme = $uri['scheme'] ?? self::SCHEME_NULL;

        if (!in_array($scheme, [self::SCHEME_LDAP, self::SCHEME_LDAPS], true)) {
            $scheme = self::SCHEME_NULL;
        }

        $host = $uri['host'] ?? 'localhost';
        $port = $uri['port'] ?? (self::SCHEME_LDAPS === $scheme ? 636 : 389);

        if (($uri['query'] ?? null) === 'tls') {
            $encryption = self::ENCRYPTION_TLS;
        } else {
            $encryption = self::SCHEME_LDAPS === $scheme
                ? self::ENCRYPTION_SSL
                : self::ENCRYPTION_NONE;
        }

        $this->username = $uri['user'] ?? null;
        $this->password = $uri['pass'] ?? null;

        if (self::SCHEME_NULL !== $scheme && null !== $this->basedn) {
            $this->ldap = Ldap::create('ext_ldap', [
                'host'       => $host,
                'port'       => $port,
                'encryption' => $encryption,
            ]);
        }
    }

    /**
     * @see LdapInterface::findUser
     */
    public function findUser(string $email, ?string &$dn, ?string &$fullname): bool
    {
        if (null === $this->ldap) {
            $this->logger->info('LDAP connection is not configured.');

            return false;
        }

        try {
            $this->ldap->bind($this->username, $this->password);

            $filter = sprintf('(mail=%s)', $this->ldap->escape($email, '', \Symfony\Component\Ldap\LdapInterface::ESCAPE_FILTER));
            $query  = $this->ldap->query($this->basedn, $filter);
            $result = $query->execute()->toArray();
        } catch (ExceptionInterface $exception) {
            $this->logger->debug($exception->getMessage());

            return false;
        }

        if (0 === count($result)) {
            $this->logger->error('LDAP account cannot be found.', [
                'basedn' => $this->basedn,
                'filter' => $filter,
            ]);

            return false;
        }

        $dn         = $result[0]->getDn();
        $attributes = $result[0]->getAttributes();

        if ($attributes['displayName'][0] ?? false) {
            $this->logger->info('Display name found.');
            $fullname = $attributes['displayName'][0];
        } elseif (($attributes['givenName'][0] ?? false) && ($attributes['sn'][0] ?? false)) {
            $this->logger->info('Given name found.');
            $fullname = sprintf('%s %s', $attributes['givenName'][0], $attributes['sn'][0]);
        } else {
            $this->logger->info('Common name found.');
            $fullname = $attributes['cn'][0];
        }

        return true;
    }

    /**
     * @see LdapInterface::checkCredentials
     */
    public function checkCredentials(string $dn, string $password): bool
    {
        if (null === $this->ldap) {
            $this->logger->info('LDAP connection is not configured.');

            return false;
        }

        try {
            $this->ldap->bind($dn, $password);
        } catch (ExceptionInterface $exception) {
            $this->logger->debug($exception->getMessage());

            return false;
        }

        return true;
    }
}
