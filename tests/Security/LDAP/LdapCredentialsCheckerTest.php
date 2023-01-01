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

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\LDAP\LdapCredentialsChecker
 */
final class LdapCredentialsCheckerTest extends WebTestCase
{
    private LdapInterface           $ldap;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldap = $this->createMock(LdapInterface::class);
        $this->ldap
            ->method('checkCredentials')
            ->willReturnMap([
                ['uid=einstein,dc=example,dc=com', 'secret', true],
                ['uid=einstein,dc=example,dc=com', 'wrong', false],
            ])
        ;

        $doctrine = self::getContainer()->get('doctrine');

        $this->repository = $doctrine->getRepository(User::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testSuccess(): void
    {
        $user = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');

        $credentialsChecker = new LdapCredentialsChecker($this->ldap);

        self::assertTrue($credentialsChecker('secret', $user));
    }

    /**
     * @covers ::__invoke
     */
    public function testWrongPassword(): void
    {
        $user = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');

        $credentialsChecker = new LdapCredentialsChecker($this->ldap);

        self::assertFalse($credentialsChecker('wrong', $user));
    }

    /**
     * @covers ::__invoke
     */
    public function testNotUser(): void
    {
        $user = new class() implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return '';
            }
        };

        $credentialsChecker = new LdapCredentialsChecker($this->ldap);

        self::assertFalse($credentialsChecker('secret', $user));
    }

    /**
     * @covers ::__invoke
     */
    public function testInternalUser(): void
    {
        $user = $this->repository->findOneByEmail('artem@example.com');

        $credentialsChecker = new LdapCredentialsChecker($this->ldap);

        self::assertFalse($credentialsChecker('secret', $user));
    }
}
