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

namespace App\Repository\Contracts;

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\User;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Interface to the 'FieldValue' entities repository.
 */
interface FieldValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist
     */
    public function persist(FieldValue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove
     */
    public function remove(FieldValue $entity, bool $flush = false): void;

    /**
     * Returns all field values of the specified issue, which the user has access.
     *
     * @param Issue               $issue  Target issue
     * @param User                $user   User
     * @param FieldPermissionEnum $access Required access
     *
     * @return FieldValue[]
     */
    public function findAllByIssue(Issue $issue, User $user, FieldPermissionEnum $access = FieldPermissionEnum::ReadOnly): array;

    /**
     * Returns the latest field values of the specified issue, which the user has access for modification.
     *
     * @param Issue $issue Target issue
     * @param User  $user  User
     *
     * @return FieldValue[]
     */
    public function getLatestValues(Issue $issue, User $user): array;

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
     * Converts specified field value from internal representation to a human-readable one.
     *
     * @param FieldTypeEnum $type  Field type
     * @param null|int      $value Field value
     *
     * @return null|bool|int|ListItem|string Human-readable value
     */
    public function getFieldValue(FieldTypeEnum $type, ?int $value): null|bool|int|string|ListItem;

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
