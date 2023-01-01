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

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * A constraint to check that value belongs to specified duration range.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class DurationRange extends Constraint
{
    public const PCRE_PATTERN = '/^\d+:[0-5]\d$/';

    /**
     * @var null|string This required option is the "min" value. Validation will fail if the given value is less than this min value
     */
    public ?string $min = null;

    /**
     * @var null|string This required option is the "max" value. Validation will fail if the given value is greater than this max value
     */
    public ?string $max = null;

    /**
     * @var string The message that will be shown if the underlying value is not in the specified range
     */
    public string $notInRangeMessage = 'This value should be between {{ min }} and {{ max }}.';

    /**
     * @var string The message that will be shown if the underlying value is less than the min option
     */
    public string $minMessage = 'This value should be {{ limit }} or more.';

    /**
     * @var string The message that will be shown if the underlying value is more than the max option
     */
    public string $maxMessage = 'This value should be {{ limit }} or less.';

    /**
     * @var string The message that will be shown if the underlying value is not a duration
     */
    public string $invalidMessage = 'This value is not valid.';

    public function __construct(?array $options = null)
    {
        parent::__construct($options);

        if (null === $this->min && null === $this->max) {
            throw new MissingOptionsException(sprintf('Either option "min" or "max" must be given for constraint "%s".', __CLASS__), ['min', 'max']);
        }

        if (null !== $this->min && !preg_match(self::PCRE_PATTERN, $this->min)) {
            throw new InvalidOptionsException(sprintf('The "min" option given for constraint "%s" is invalid.', __CLASS__), ['min']);
        }

        if (null !== $this->max && !preg_match(self::PCRE_PATTERN, $this->max)) {
            throw new InvalidOptionsException(sprintf('The "max" option given for constraint "%s" is invalid.', __CLASS__), ['max']);
        }
    }
}
