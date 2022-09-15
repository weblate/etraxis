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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Field
 */
final class FieldTest extends WebTestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);

        self::assertSame($state, $field->getState());
        self::assertSame(FieldTypeEnum::List, $field->getType());
        self::assertEmpty($field->getRolePermissions());
        self::assertEmpty($field->getGroupPermissions());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);

        $this->setProperty($field, 'id', 1);
        self::assertSame(1, $field->getId());
    }

    /**
     * @covers ::getState
     */
    public function testState(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertSame($state, $field->getState());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);

        $field->setName('Priority');
        self::assertSame('Priority', $field->getName());
    }

    /**
     * @covers ::getType
     */
    public function testType(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertSame(FieldTypeEnum::List, $field->getType());
    }

    /**
     * @covers ::getDescription
     * @covers ::setDescription
     */
    public function testDescription(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertNull($field->getDescription());

        $field->setDescription('Lorem Ipsum');
        self::assertSame('Lorem Ipsum', $field->getDescription());
    }

    /**
     * @covers ::getPosition
     * @covers ::setPosition
     */
    public function testPosition(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);

        $field->setPosition(1);
        self::assertSame(1, $field->getPosition());
    }

    /**
     * @covers ::isRequired
     * @covers ::setRequired
     */
    public function testRequired(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);

        $field->setRequired(false);
        self::assertFalse($field->isRequired());

        $field->setRequired(true);
        self::assertTrue($field->isRequired());
    }

    /**
     * @covers ::isRemoved
     * @covers ::remove
     */
    public function testRemovedAt(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertFalse($field->isRemoved());

        $field->remove();
        self::assertTrue($field->isRemoved());
    }

    /**
     * @covers ::getAllParameters
     * @covers ::getParameter
     * @covers ::setParameter
     */
    public function testParameters(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertEmpty($field->getAllParameters());
        self::assertNull($field->getParameter('text'));

        $field->setParameter('text', 'foo');
        $field->setParameter('number', 123);

        $expected = [
            'text'   => 'foo',
            'number' => 123,
        ];

        self::assertSame($expected, $field->getAllParameters());
        self::assertSame('foo', $field->getParameter('text'));
        self::assertSame(123, $field->getParameter('number'));

        $field->setParameter('text', null);

        $expected = [
            'number' => 123,
        ];

        self::assertSame($expected, $field->getAllParameters());
        self::assertNull($field->getParameter('text'));
        self::assertSame(123, $field->getParameter('number'));
    }

    /**
     * @covers ::getRolePermissions
     */
    public function testRolePermissions(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertEmpty($field->getRolePermissions());

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($field, 'rolePermissions');
        $permissions->add('Permission A');
        $permissions->add('Permission B');

        self::assertSame(['Permission A', 'Permission B'], $field->getRolePermissions()->getValues());
    }

    /**
     * @covers ::getGroupPermissions
     */
    public function testGroupPermissions(): void
    {
        $state = new State(new Template(new Project()), StateTypeEnum::Intermediate);

        $field = new Field($state, FieldTypeEnum::List);
        self::assertEmpty($field->getGroupPermissions());

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($field, 'groupPermissions');
        $permissions->add('Permission A');
        $permissions->add('Permission B');

        self::assertSame(['Permission A', 'Permission B'], $field->getGroupPermissions()->getValues());
    }
}
