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

namespace App\Entity;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\LocaleEnum;
use App\Entity\Enums\ThemeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\User
 */
final class UserTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $uuid_pattern = '/^([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})$/';

        $user = new User();

        self::assertFalse($user->isAdmin());
        self::assertFalse($user->isDisabled());
        self::assertSame(AccountProviderEnum::eTraxis, $user->getAccountProvider());
        self::assertMatchesRegularExpression($uuid_pattern, $user->getAccountUid());
        self::assertEmpty($user->getGroups());
    }

    /**
     * @covers ::getUserIdentifier
     */
    public function testUserIdentifier(): void
    {
        $user = new User();

        $user->setEmail('anna@example.com');
        self::assertSame('anna@example.com', $user->getUserIdentifier());
    }

    /**
     * @covers ::getRoles
     */
    public function testRoles(): void
    {
        $user = new User();
        self::assertSame([User::ROLE_USER], $user->getRoles());

        $user->setAdmin(true);
        self::assertSame([User::ROLE_ADMIN], $user->getRoles());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $user = new User();

        $this->setProperty($user, 'id', 1);
        self::assertSame(1, $user->getId());
    }

    /**
     * @covers ::getEmail
     * @covers ::setEmail
     */
    public function testEmail(): void
    {
        $user = new User();

        $user->setEmail('anna@example.com');
        self::assertSame('anna@example.com', $user->getEmail());
    }

    /**
     * @covers ::getEmailAddress
     */
    public function testEmailAddress(): void
    {
        $user = new User();

        $user->setEmail('anna@example.com');
        $user->setFullname('Anna Rodygina');

        self::assertSame('anna@example.com', $user->getEmailAddress()->getAddress());
        self::assertSame('Anna Rodygina', $user->getEmailAddress()->getName());
    }

    /**
     * @covers ::getPassword
     * @covers ::setPassword
     */
    public function testPassword(): void
    {
        $user = new User();
        self::assertNull($user->getPassword());

        $user->setPassword('secret');
        self::assertSame('secret', $user->getPassword());
    }

    /**
     * @covers ::clearResetToken
     * @covers ::generateResetToken
     * @covers ::isResetTokenValid
     */
    public function testResetToken(): void
    {
        $user = new User();

        $token1 = $user->generateResetToken(new \DateInterval('PT1M'));
        self::assertMatchesRegularExpression('/^([[:xdigit:]]{32}$)/', $token1);
        self::assertTrue($user->isResetTokenValid($token1));

        $token2 = $user->generateResetToken(new \DateInterval('PT1M'));
        self::assertFalse($user->isResetTokenValid($token1));
        self::assertTrue($user->isResetTokenValid($token2));

        $user->clearResetToken();
        self::assertFalse($user->isResetTokenValid($token2));

        $token = $user->generateResetToken(new \DateInterval('PT0M'));
        self::assertFalse($user->isResetTokenValid($token));
    }

    /**
     * @covers ::getFullname
     * @covers ::setFullname
     */
    public function testFullname(): void
    {
        $user = new User();

        $user->setFullname('Anna Rodygina');
        self::assertSame('Anna Rodygina', $user->getFullname());
    }

    /**
     * @covers ::getDescription
     * @covers ::setDescription
     */
    public function testDescription(): void
    {
        $user = new User();
        self::assertNull($user->getDescription());

        $user->setDescription('Very lovely daughter');
        self::assertSame('Very lovely daughter', $user->getDescription());
    }

    /**
     * @covers ::isAdmin
     * @covers ::setAdmin
     */
    public function testAdmin(): void
    {
        $user = new User();
        self::assertFalse($user->isAdmin());

        $user->setAdmin(true);
        self::assertTrue($user->isAdmin());

        $user->setAdmin(false);
        self::assertFalse($user->isAdmin());
    }

    /**
     * @covers ::isDisabled
     * @covers ::setDisabled
     */
    public function testDisabled(): void
    {
        $user = new User();
        self::assertFalse($user->isDisabled());

        $user->setDisabled(true);
        self::assertTrue($user->isDisabled());

        $user->setDisabled(false);
        self::assertFalse($user->isDisabled());
    }

    /**
     * @covers ::getAccountProvider
     * @covers ::setAccountProvider
     */
    public function testAccountProvider(): void
    {
        $user = new User();
        self::assertSame(AccountProviderEnum::eTraxis, $user->getAccountProvider());

        $user->setAccountProvider(AccountProviderEnum::LDAP);
        self::assertSame(AccountProviderEnum::LDAP, $user->getAccountProvider());
    }

    /**
     * @covers ::getAccountUid
     * @covers ::setAccountUid
     */
    public function testAccountUid(): void
    {
        $expected = '80fe8ef1-00ba-4d37-9028-6d92db603c91';

        $user = new User();
        self::assertNotSame($expected, $user->getAccountUid());

        $user->setAccountUid($expected);
        self::assertSame($expected, $user->getAccountUid());
    }

    /**
     * @covers ::isAccountExternal
     */
    public function testIsAccountExternal(): void
    {
        $user = new User();
        self::assertFalse($user->isAccountExternal());

        $user->setAccountProvider(AccountProviderEnum::LDAP);
        self::assertTrue($user->isAccountExternal());

        $user->setAccountProvider(AccountProviderEnum::eTraxis);
        self::assertFalse($user->isAccountExternal());
    }

    /**
     * @covers ::getLocale
     * @covers ::setLocale
     */
    public function testLocale(): void
    {
        $user = new User();
        self::assertSame(LocaleEnum::English, $user->getLocale());

        $user->setLocale(LocaleEnum::Russian);
        self::assertSame(LocaleEnum::Russian, $user->getLocale());
    }

    /**
     * @covers ::getTheme
     * @covers ::setTheme
     */
    public function testTheme(): void
    {
        $user = new User();
        self::assertSame(ThemeEnum::Azure, $user->getTheme());

        $user->setTheme(ThemeEnum::Emerald);
        self::assertSame(ThemeEnum::Emerald, $user->getTheme());
    }

    /**
     * @covers ::getTimezone
     * @covers ::setTimezone
     */
    public function testTimezone(): void
    {
        $user = new User();
        self::assertSame('UTC', $user->getTimezone());

        $user->setTimezone('Pacific/Auckland');
        self::assertSame('Pacific/Auckland', $user->getTimezone());

        $user->setTimezone('Unknown');
        self::assertSame('Pacific/Auckland', $user->getTimezone());
    }

    /**
     * @covers ::getGroups
     */
    public function testGroups(): void
    {
        $user = new User();
        self::assertEmpty($user->getGroups());

        /** @var \Doctrine\Common\Collections\Collection $groups */
        $groups = $this->getProperty($user, 'groups');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $user->getGroups()->getValues());
    }
}
