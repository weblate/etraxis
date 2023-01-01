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

use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\PasswordHasher\Md5PasswordHasher
 */
final class Md5PasswordHasherTest extends TestCase
{
    private Md5PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new Md5PasswordHasher();
    }

    /**
     * @covers ::hash
     */
    public function testHash(): void
    {
        self::assertSame('8dbdda48fb8748d6746f1965824e966a', $this->hasher->hash('simple'));
    }

    /**
     * @covers ::hash
     */
    public function testHashMaxLength(): void
    {
        $raw = str_repeat('*', PasswordHasherInterface::MAX_PASSWORD_LENGTH);

        try {
            $this->hasher->hash($raw);
        } catch (\Exception) {
            self::fail();
        }

        self::assertTrue(true);
    }

    /**
     * @covers ::hash
     */
    public function testHashTooLong(): void
    {
        $this->expectException(BadCredentialsException::class);

        $raw = str_repeat('*', PasswordHasherInterface::MAX_PASSWORD_LENGTH + 1);

        $this->hasher->hash($raw);
    }

    /**
     * @covers ::verify
     */
    public function testVerify(): void
    {
        $encoded = '8dbdda48fb8748d6746f1965824e966a';
        $valid   = 'simple';
        $invalid = 'invalid';

        self::assertTrue($this->hasher->verify($encoded, $valid));
        self::assertFalse($this->hasher->verify($encoded, $invalid));
    }

    /**
     * @covers ::needsRehash
     */
    public function testNeedsRehash(): void
    {
        $encoded = '8dbdda48fb8748d6746f1965824e966a';

        self::assertTrue($this->hasher->needsRehash($encoded));
    }
}
