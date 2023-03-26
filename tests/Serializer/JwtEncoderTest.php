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

namespace App\Serializer;

use App\Utils\SecondsEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\JwtEncoder
 */
final class JwtEncoderTest extends TestCase
{
    private JwtEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = new JwtEncoder('$ecretf0rt3st');
    }

    /**
     * @covers ::decode
     * @covers ::encode
     */
    public function testEncodeDecode(): void
    {
        $data = [
            'sub' => 'artem@example.com',
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ];

        $token = $this->encoder->encode($data, 'jwt');

        self::assertSame($data, $this->encoder->decode($token, 'jwt'));
    }

    /**
     * @covers ::supportsEncoding
     */
    public function testSupportsEncoding(): void
    {
        self::assertTrue($this->encoder->supportsEncoding('jwt'));
        self::assertFalse($this->encoder->supportsEncoding('json'));
    }

    /**
     * @covers ::supportsDecoding
     */
    public function testSupportsDecoding(): void
    {
        self::assertTrue($this->encoder->supportsDecoding('jwt'));
        self::assertFalse($this->encoder->supportsDecoding('json'));
    }
}
