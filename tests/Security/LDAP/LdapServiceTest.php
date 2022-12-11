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

use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\LDAP\LdapService
 */
final class LdapServiceTest extends TestCase
{
    use ReflectionTrait;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @covers ::__construct
     */
    public function testNull(): void
    {
        $service = new LdapService($this->logger, null, 'dc=example,dc=com');

        self::assertNull($this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNull($this->getProperty($service, 'ldap'));
    }

    /**
     * @covers ::__construct
     */
    public function testNone(): void
    {
        $service = new LdapService($this->logger, 'null://example.com', 'dc=example,dc=com');

        self::assertNull($this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNull($this->getProperty($service, 'ldap'));
    }

    /**
     * @covers ::__construct
     */
    public function testLdapWithUser(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        self::assertSame('root', $this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNotNull($this->getProperty($service, 'ldap'));

        /** @var AdapterInterface $adapter */
        $ldap    = $this->getProperty($service, 'ldap');
        $adapter = $this->getProperty($ldap, 'adapter');
        $config  = $this->getProperty($adapter->getConnection(), 'config');

        self::assertSame('ldap://example.com:389', $config['connection_string']);
        self::assertSame('none', $config['encryption']);
    }

    /**
     * @covers ::__construct
     */
    public function testLdapsWithUserPassword(): void
    {
        $service = new LdapService($this->logger, 'ldaps://root:secret@example.com', 'dc=example,dc=com');

        self::assertSame('root', $this->getProperty($service, 'username'));
        self::assertSame('secret', $this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNotNull($this->getProperty($service, 'ldap'));

        /** @var AdapterInterface $adapter */
        $ldap    = $this->getProperty($service, 'ldap');
        $adapter = $this->getProperty($ldap, 'adapter');
        $config  = $this->getProperty($adapter->getConnection(), 'config');

        self::assertSame('ldaps://example.com:636', $config['connection_string']);
        self::assertSame('ssl', $config['encryption']);
    }

    /**
     * @covers ::__construct
     */
    public function testPort(): void
    {
        $service = new LdapService($this->logger, 'ldap://example.com:1000', 'dc=example,dc=com');

        self::assertNull($this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNotNull($this->getProperty($service, 'ldap'));

        /** @var AdapterInterface $adapter */
        $ldap    = $this->getProperty($service, 'ldap');
        $adapter = $this->getProperty($ldap, 'adapter');
        $config  = $this->getProperty($adapter->getConnection(), 'config');

        self::assertSame('ldap://example.com:1000', $config['connection_string']);
        self::assertSame('none', $config['encryption']);
    }

    /**
     * @covers ::__construct
     */
    public function testTls(): void
    {
        $service = new LdapService($this->logger, 'ldap://example.com?tls', 'dc=example,dc=com');

        self::assertNull($this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNotNull($this->getProperty($service, 'ldap'));

        /** @var AdapterInterface $adapter */
        $ldap    = $this->getProperty($service, 'ldap');
        $adapter = $this->getProperty($ldap, 'adapter');
        $config  = $this->getProperty($adapter->getConnection(), 'config');

        self::assertSame('ldap://example.com:389', $config['connection_string']);
        self::assertSame('tls', $config['encryption']);
    }

    /**
     * @covers ::__construct
     */
    public function testMaximum(): void
    {
        $service = new LdapService($this->logger, 'ldap://root:secret@example.com:636?tls', 'dc=example,dc=com');

        self::assertSame('root', $this->getProperty($service, 'username'));
        self::assertSame('secret', $this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNotNull($this->getProperty($service, 'ldap'));

        /** @var AdapterInterface $adapter */
        $ldap    = $this->getProperty($service, 'ldap');
        $adapter = $this->getProperty($ldap, 'adapter');
        $config  = $this->getProperty($adapter->getConnection(), 'config');

        self::assertSame('ldap://example.com:636', $config['connection_string']);
        self::assertSame('tls', $config['encryption']);
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidSchema(): void
    {
        $service = new LdapService($this->logger, 'ssh://root:secret@example.com', 'dc=example,dc=com');

        self::assertSame('root', $this->getProperty($service, 'username'));
        self::assertSame('secret', $this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNull($this->getProperty($service, 'ldap'));
    }

    /**
     * @covers ::__construct
     */
    public function testEmptyHost(): void
    {
        $service = new LdapService($this->logger, 'ldap://root:secret@', 'dc=example,dc=com');

        self::assertNull($this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertSame('dc=example,dc=com', $this->getProperty($service, 'basedn'));
        self::assertNull($this->getProperty($service, 'ldap'));
    }

    /**
     * @covers ::__construct
     */
    public function testEmptyBaseDN(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', null);

        self::assertSame('root', $this->getProperty($service, 'username'));
        self::assertNull($this->getProperty($service, 'password'));
        self::assertNull($this->getProperty($service, 'basedn'));
        self::assertNull($this->getProperty($service, 'ldap'));
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserSuccessOnDisplayName(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getDn')
            ->willReturn('uid=newton,dc=example,dc=com')
        ;
        $entry
            ->method('getAttributes')
            ->willReturn([
                'mail'        => ['newton@example.com'],
                'displayName' => ['Isaac Newton'],
            ])
        ;

        $collection = $this->createMock(CollectionInterface::class);
        $collection
            ->method('toArray')
            ->willReturn([$entry])
        ;

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true)
        ;
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com')
        ;
        $ldap
            ->method('query')
            ->willReturn($query)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertTrue($service->findUser('newton@example.com', $dn, $fullname));
        self::assertSame('uid=newton,dc=example,dc=com', $dn);
        self::assertSame('Isaac Newton', $fullname);
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserSuccessOnGivenName(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getDn')
            ->willReturn('uid=newton,dc=example,dc=com')
        ;
        $entry
            ->method('getAttributes')
            ->willReturn([
                'mail'      => ['newton@example.com'],
                'givenName' => ['Isaac'],
                'sn'        => ['Newton'],
            ])
        ;

        $collection = $this->createMock(CollectionInterface::class);
        $collection
            ->method('toArray')
            ->willReturn([$entry])
        ;

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true)
        ;
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com')
        ;
        $ldap
            ->method('query')
            ->willReturn($query)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertTrue($service->findUser('newton@example.com', $dn, $fullname));
        self::assertSame('uid=newton,dc=example,dc=com', $dn);
        self::assertSame('Isaac Newton', $fullname);
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserSuccessOnCommonName(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getDn')
            ->willReturn('uid=newton,dc=example,dc=com')
        ;
        $entry
            ->method('getAttributes')
            ->willReturn([
                'mail' => ['newton@example.com'],
                'cn'   => ['Isaac Newton'],
            ])
        ;

        $collection = $this->createMock(CollectionInterface::class);
        $collection
            ->method('toArray')
            ->willReturn([$entry])
        ;

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true)
        ;
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com')
        ;
        $ldap
            ->method('query')
            ->willReturn($query)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertTrue($service->findUser('newton@example.com', $dn, $fullname));
        self::assertSame('uid=newton,dc=example,dc=com', $dn);
        self::assertSame('Isaac Newton', $fullname);
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserFailure(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $entry = $this->createMock(Entry::class);
        $entry
            ->method('getDn')
            ->willReturn('uid=newton,dc=example,dc=com')
        ;
        $entry
            ->method('getAttributes')
            ->willReturn([
                'mail'        => ['newton@example.com'],
                'displayName' => ['Isaac Newton'],
            ])
        ;

        $collection = $this->createMock(CollectionInterface::class);
        $collection
            ->method('toArray')
            ->willReturn([$entry])
        ;

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willThrowException(new ConnectionException())
        ;
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com')
        ;
        $ldap
            ->method('query')
            ->willReturn($query)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertFalse($service->findUser('newton@example.com', $dn, $fullname));
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserNoEntries(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $collection = $this->createMock(CollectionInterface::class);
        $collection
            ->method('toArray')
            ->willReturn([])
        ;

        $query = $this->createMock(QueryInterface::class);
        $query
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true)
        ;
        $ldap
            ->method('escape')
            ->willReturn('newton@example.com')
        ;
        $ldap
            ->method('query')
            ->willReturn($query)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertFalse($service->findUser('newton@example.com', $dn, $fullname));
    }

    /**
     * @covers ::findUser
     */
    public function testFindUserDisabled(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', null);

        self::assertFalse($service->findUser('newton@example.com', $dn, $fullname));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsSuccess(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willReturn(true)
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertTrue($service->checkCredentials('uid=newton,dc=example,dc=com', 'password'));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsFailure(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', 'dc=example,dc=com');

        $ldap = $this->createMock(\Symfony\Component\Ldap\LdapInterface::class);
        $ldap
            ->method('bind')
            ->willThrowException(new ConnectionException())
        ;

        $this->setProperty($service, 'ldap', $ldap);

        self::assertFalse($service->checkCredentials('uid=newton,dc=example,dc=com', 'password'));
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentialsDisabled(): void
    {
        $service = new LdapService($this->logger, 'ldap://root@example.com', null);

        self::assertFalse($service->checkCredentials('uid=newton,dc=example,dc=com', 'password'));
    }
}
