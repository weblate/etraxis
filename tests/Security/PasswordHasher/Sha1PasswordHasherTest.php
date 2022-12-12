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

namespace App\Security\PasswordHasher;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\PasswordHasher\Sha1PasswordHasher
 */
final class Sha1PasswordHasherTest extends TestCase
{
    private Sha1PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new Sha1PasswordHasher();
    }

    /**
     * @covers ::hash
     */
    public function testHash(): void
    {
        self::assertSame('mzMEbtOdGC462vqQRa1nh9S7wyE=', $this->hasher->hash('legacy'));
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
        $encoded = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';
        $valid   = 'legacy';
        $invalid = 'invalid';

        self::assertTrue($this->hasher->verify($encoded, $valid));
        self::assertFalse($this->hasher->verify($encoded, $invalid));
    }

    /**
     * @covers ::needsRehash
     */
    public function testNeedsRehash(): void
    {
        $encoded = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';

        self::assertTrue($this->hasher->needsRehash($encoded));
    }
}
