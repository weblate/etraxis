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

namespace App\Repository\Contracts;

use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\ListItem;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Interface to the 'FieldValue' entities repository.
 */
interface FieldValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(FieldValue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(FieldValue $entity, bool $flush = false): void;

    /**
     * Validates specified values against the specified set of fields.
     *
     * @param Field[] $fields  List of fields
     * @param array   $values  List of values
     * @param array   $context Validation context
     *
     * @return ConstraintViolationListInterface List of violations
     */
    public function validateFieldValues(array $fields, array $values, array $context = []): ConstraintViolationListInterface;

    /**
     * Sets value of the specified field.
     *
     * @param FieldValue                    $fieldValue Field's value to be updated
     * @param null|bool|int|ListItem|string $value      Human-readable value to set
     *
     * @return bool Whether the value was successfully updated
     */
    public function setFieldValue(FieldValue $fieldValue, null|bool|int|string|ListItem $value): bool;
}
