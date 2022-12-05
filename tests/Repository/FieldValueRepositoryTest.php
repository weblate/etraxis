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

namespace App\Repository;

use App\Entity\DecimalValue;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\SecondsEnum;
use App\Entity\Event;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\State;
use App\Entity\StringValue;
use App\Entity\Template;
use App\Entity\TextValue;
use App\Entity\Transition;
use App\Entity\User;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\FieldValueRepository
 */
final class FieldValueRepositoryTest extends TransactionalTestCase
{
    private Contracts\FieldValueRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(FieldValue::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValues(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $stringValue = $this->doctrine->getRepository(StringValue::class)->get('940059027173b8e8e1e3e874681f012f1f3bcf1d');
        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos soluta excepturi omnis.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['Error', 1],
            ['Due date', $issue->getCreatedAt() + 7 * SecondsEnum::OneDay->value],
            ['Commit ID', $stringValue->getId()],
            ['Delta', 1],
            ['Effort', 80],
            ['Test coverage', null],
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 5 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(12, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByManagerToRead(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Esse labore et ducimus consequuntur labore voluptatem atque.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 6 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(4, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByManagerToWrite(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Esse labore et ducimus consequuntur labore voluptatem atque.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->findAllByIssue($issue, $user, FieldPermissionEnum::ReadAndWrite);

        $expected = [
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 6 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(4, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByDeveloperToRead(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('fdooley@example.com');

        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Esse labore et ducimus consequuntur labore voluptatem atque.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(3, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByDeveloperToWrite(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('fdooley@example.com');

        $values = $this->repository->findAllByIssue($issue, $user, FieldPermissionEnum::ReadAndWrite);

        self::assertEmpty($values);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByResponsibleToRead(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        $values = $this->repository->findAllByIssue($issue, $user);

        $expected = [
            ['Due date', $issue->getCreatedAt() + 6 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(1, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testGetAllValuesByResponsibleToWrite(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        $values = $this->repository->findAllByIssue($issue, $user, FieldPermissionEnum::ReadAndWrite);

        self::assertEmpty($values);
    }

    /**
     * @covers ::getLatestValues
     */
    public function testGetLatestValues(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $stringValue = $this->doctrine->getRepository(StringValue::class)->get('940059027173b8e8e1e3e874681f012f1f3bcf1d');
        $textValue   = $this->doctrine->getRepository(TextValue::class)->get('Velit voluptatem rerum nulla quos soluta excepturi omnis.');
        [$listItem]  = $this->doctrine->getRepository(ListItem::class)->findBy(['text' => 'high'], ['id' => 'ASC']);

        $values = $this->repository->getLatestValues($issue, $user);

        $expected = [
            ['Commit ID', $stringValue->getId()],
            ['Delta', 1],
            ['Effort', 80],
            ['Test coverage', null],
            ['Priority', $listItem->getId()],
            ['Description', $textValue->getId()],
            ['New feature', 0],
            ['Due date', $issue->getCreatedAt() + 5 * SecondsEnum::OneDay->value],
        ];

        $actual = array_map(fn (FieldValue $fieldValue) => [$fieldValue->getField()->getName(), $fieldValue->getValue()], $values);

        self::assertCount(8, $values);
        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::validateFieldValues
     */
    public function testValidateFieldValuesSuccess(): void
    {
        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $fields = $template->getInitialState()->getFields()->getValues();

        $errors = $this->repository->validateFieldValues($fields, [
            $fields[0]->getId() => 2,
            $fields[1]->getId() => str_pad('', TextValue::MAX_VALUE, '*'),
            $fields[2]->getId() => true,
        ]);

        self::assertCount(3, $fields);
        self::assertCount(0, $errors);
    }

    /**
     * @covers ::validateFieldValues
     */
    public function testValidateFieldValuesInvalid(): void
    {
        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $fields = $template->getInitialState()->getFields()->getValues();
        $errors = $this->repository->validateFieldValues($fields, [
            $fields[0]->getId() => 4,
            $fields[1]->getId() => str_pad('', TextValue::MAX_VALUE + 1, '*'),
            $fields[2]->getId() => 0,
        ]);

        self::assertCount(3, $fields);
        self::assertCount(3, $errors);
        self::assertSame('The value you selected is not a valid choice.', $errors->get(0)->getMessage());
        self::assertSame('This value is too long. It should have 10000 characters or less.', $errors->get(1)->getMessage());
        self::assertSame('\'New feature\' should be \'false\' or \'true\'.', $errors->get(2)->getMessage());
    }

    /**
     * @covers ::validateFieldValues
     */
    public function testValidateFieldValuesMissing(): void
    {
        /** @var Template $template */
        [/* skipping */ , /* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $fields = $template->getInitialState()->getFields()->toArray();
        $errors = $this->repository->validateFieldValues($fields, []);

        self::assertCount(3, $fields);
        self::assertCount(1, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetCheckboxFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Checkbox === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertTrue($this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDateFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Date === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        /** @var Event $event */
        $event = $issue->getEvents()->first();

        self::assertSame(5 * SecondsEnum::OneDay->value, $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()) - $event->getCreatedAt());
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDecimalFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Decimal === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame('98.49', $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetDurationFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Duration === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame('1:20', $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetIssueFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [$duplicate] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Issue === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame($duplicate->getFullId(), $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetListFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::List === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        /** @var ListItem $item */
        $item = $this->repository->getFieldValue($value->getField()->getType(), $value->getValue());

        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame('normal', $item->getText());
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetNumberFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Number === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame(5173, $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetStringFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::String === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame('940059027173b8e8e1e3e874681f012f1f3bcf1d', $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetTextFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::Text === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertSame('Quas sunt reprehenderit vero accusantium.', $this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::getFieldValue
     */
    public function testGetNullFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        $values = $this->repository->getLatestValues($issue, $user);
        $values = array_filter($values, fn (FieldValue $fieldValue) => FieldTypeEnum::String === $fieldValue->getField()->getType());

        /** @var FieldValue $value */
        $value = reset($values);

        self::assertNull($this->repository->getFieldValue($value->getField()->getType(), $value->getValue()));
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetCheckboxFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, false);
        self::assertTrue($result);
        self::assertSame(0, $fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, true);
        self::assertTrue($result);
        self::assertSame(1, $fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDateFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, '2015-04-23');
        self::assertTrue($result);

        $date = date_create();
        $date->setTimestamp($fieldValue->getValue())->setTimezone(timezone_open('UTC'));
        self::assertSame('2015-04-23', $date->format('Y-m-d'));

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDecimalFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Test coverage'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, '3.1415');
        self::assertTrue($result);
        self::assertSame('3.1415', $this->doctrine->getRepository(DecimalValue::class)->find($fieldValue->getValue())->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetDurationFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, '11:52');
        self::assertTrue($result);
        self::assertSame(712, $fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetIssueFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $duplicate */
        [$duplicate] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, $duplicate->getId());
        self::assertTrue($result);
        self::assertSame($duplicate->getId(), $fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, self::UNKNOWN_ENTITY_ID);
        self::assertFalse($result);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetListFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, 3);
        self::assertTrue($result);
        self::assertSame('low', $this->doctrine->getRepository(ListItem::class)->find($fieldValue->getValue())->getText());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, 4);
        self::assertFalse($result);
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetNumberFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, 1234);
        self::assertTrue($result);
        self::assertSame(1234, $fieldValue->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetStringFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, 'fb6c40d246aeeb8934884febcd18d19555fd7725');
        self::assertTrue($result);
        self::assertSame('fb6c40d246aeeb8934884febcd18d19555fd7725', $this->doctrine->getRepository(StringValue::class)->find($fieldValue->getValue())->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }

    /**
     * @covers ::setFieldValue
     */
    public function testSetTextFieldValue(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $event      = new Event($issue, $user, EventTypeEnum::StateChanged, $state->getName());
        $transition = new Transition($event, $state);
        $fieldValue = new FieldValue($transition, $field, null);

        $result = $this->repository->setFieldValue($fieldValue, 'Corporis ea amet eligendi fugit.');
        self::assertTrue($result);
        self::assertSame('Corporis ea amet eligendi fugit.', $this->doctrine->getRepository(TextValue::class)->find($fieldValue->getValue())->getValue());

        $result = $this->repository->setFieldValue($fieldValue, null);
        self::assertTrue($result);
        self::assertNull($fieldValue->getValue());
    }
}
