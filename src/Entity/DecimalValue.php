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

namespace App\Entity;

use App\Repository\DecimalValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Decimal value.
 */
#[ORM\Entity(repositoryClass: DecimalValueRepository::class)]
#[ORM\Table(name: 'decimal_values')]
#[ORM\UniqueConstraint(fields: ['value'])]
class DecimalValue
{
    // Constraints.
    public const MIN_VALUE = '-9999999999.9999999999';
    public const MAX_VALUE = '9999999999.9999999999';
    public const PRECISION = 10;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Decimal value.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 10)]
    protected string $value;

    /**
     * Creates new decimal value.
     *
     * @param string $value String representation of the value
     */
    public function __construct(string $value)
    {
        $this->value = self::trim($value);
    }

    /**
     * Property getter.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    public function getValue(): string
    {
        return self::trim($this->value);
    }

    /**
     * Trims leading and trailing extra zeros from the specified number.
     */
    protected static function trim(string $value): string
    {
        $value = str_contains($value, '.')
            ? trim($value, '0')
            : ltrim($value, '0');

        if ('' === $value) {
            $value = '0';
        } elseif ('.' === $value[0]) {
            $value = '0'.$value;
        }

        return rtrim($value, '.');
    }
}
