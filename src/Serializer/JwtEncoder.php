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

namespace App\Serializer;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * JSON Web Token encoder/decoder.
 */
class JwtEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'jwt';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function encode(mixed $data, string $format, array $context = []): string
    {
        return JWT::encode($data, $this->secret, 'HS256');
    }

    /**
     * {@inheritDoc}
     */
    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data, string $format, array $context = []): array
    {
        $payload = JWT::decode($data, new Key($this->secret, 'HS256'));

        return get_object_vars($payload);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
