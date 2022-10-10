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

use App\Repository\FieldValueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Field value.
 */
#[ORM\Entity(repositoryClass: FieldValueRepository::class)]
#[ORM\Table(name: 'field_values')]
#[ORM\UniqueConstraint(fields: ['transition', 'field'])]
class FieldValue
{
    // Date constraints.
    public const MIN_DATE_VALUE = -0x80000000;
    public const MAX_DATE_VALUE = 0x7FFFFFFF;

    // Duration constraints.
    public const MIN_DURATION_VALUE = 0;            // 0:00
    public const MAX_DURATION_VALUE = 59999999;     // 999999:59

    // Number constraints.
    public const MIN_NUMBER_VALUE = -1000000000;
    public const MAX_NUMBER_VALUE = 1000000000;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Transition when the value was created.
     */
    #[ORM\ManyToOne(inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Transition $transition;

    /**
     * Field which value was created.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected Field $field;

    /**
     * New value of the field.
     *
     * Depends on the field type as following (@see FieldTypeEnum enum):
     *     Checkbox - state of checkbox (0 - unchecked, 1 - checked)
     *     Date     - date value (Unix Epoch timestamp)
     *     Decimal  - decimal value (foreign key to @see DecimalValue entity)
     *     Duration - duration value (total number of minutes from 0:00 to 999999:59)
     *     Issue    - issue ID (foreign key to @see Issue entity)
     *     List     - integer value (foreign key to @see ListItem entity)
     *     Number   - integer value (from -1000000000 to +1000000000)
     *     String   - string value (foreign key to @see StringValue entity)
     *     Text     - text value (foreign key to @see TextValue entity)
     */
    #[ORM\Column(nullable: true)]
    protected ?int $value = null;

    /**
     * Creates new field value.
     */
    public function __construct(Transition $transition, Field $field, ?int $value)
    {
        if ($transition->getState() !== $field->getState()) {
            throw new \UnexpectedValueException('Unknown field: '.$field->getName());
        }

        $this->transition = $transition;
        $this->field      = $field;
        $this->value      = $value;
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
    public function getTransition(): Transition
    {
        return $this->transition;
    }

    /**
     * Property getter.
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * Property getter.
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * Property setter.
     */
    public function setValue(?int $value): self
    {
        $this->value = $value;

        return $this;
    }
}
