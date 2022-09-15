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

use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Template
 */
final class TemplateTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project  = new Project();
        $template = new Template($project);

        self::assertSame($project, $template->getProject());
        self::assertTrue($template->isLocked());
        self::assertEmpty($template->getStates());
        self::assertEmpty($template->getRolePermissions());
        self::assertEmpty($template->getGroupPermissions());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $template = new Template(new Project());

        $this->setProperty($template, 'id', 1);
        self::assertSame(1, $template->getId());
    }

    /**
     * @covers ::getProject
     */
    public function testProject(): void
    {
        $project = new Project();

        $template = new Template($project);
        self::assertSame($project, $template->getProject());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName(): void
    {
        $template = new Template(new Project());

        $template->setName('Bug report');
        self::assertSame('Bug report', $template->getName());
    }

    /**
     * @covers ::getPrefix
     * @covers ::setPrefix
     */
    public function testPrefix(): void
    {
        $template = new Template(new Project());

        $template->setPrefix('bug');
        self::assertSame('bug', $template->getPrefix());
    }

    /**
     * @covers ::getDescription
     * @covers ::setDescription
     */
    public function testDescription(): void
    {
        $template = new Template(new Project());
        self::assertNull($template->getDescription());

        $template->setDescription('Lorem Ipsum');
        self::assertSame('Lorem Ipsum', $template->getDescription());
    }

    /**
     * @covers ::isLocked
     * @covers ::setLocked
     */
    public function testLocked(): void
    {
        $template = new Template(new Project());
        self::assertTrue($template->isLocked());

        $template->setLocked(false);
        self::assertFalse($template->isLocked());

        $template->setLocked(true);
        self::assertTrue($template->isLocked());
    }

    /**
     * @covers ::getCriticalAge
     * @covers ::setCriticalAge
     */
    public function testCriticalAge(): void
    {
        $template = new Template(new Project());
        self::assertNull($template->getCriticalAge());

        $template->setCriticalAge(10);
        self::assertSame(10, $template->getCriticalAge());

        $template->setCriticalAge(null);
        self::assertNull($template->getCriticalAge());
    }

    /**
     * @covers ::getFrozenTime
     * @covers ::setFrozenTime
     */
    public function testFrozenTime(): void
    {
        $template = new Template(new Project());
        self::assertNull($template->getFrozenTime());

        $template->setFrozenTime(10);
        self::assertSame(10, $template->getFrozenTime());

        $template->setFrozenTime(null);
        self::assertNull($template->getFrozenTime());
    }

    /**
     * @covers ::getInitialState
     */
    public function testInitialState(): void
    {
        $template = new Template(new Project());
        self::assertNull($template->getInitialState());

        $initial      = new State($template, StateTypeEnum::Initial);
        $intermediate = new State($template, StateTypeEnum::Intermediate);
        $final        = new State($template, StateTypeEnum::Final);

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');

        $states->add($intermediate);
        $states->add($final);
        self::assertNull($template->getInitialState());

        $states->add($initial);
        self::assertSame($initial, $template->getInitialState());
    }

    /**
     * @covers ::getStates
     */
    public function testStates(): void
    {
        $template = new Template(new Project());
        self::assertEmpty($template->getStates());

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add('State A');
        $states->add('State B');

        self::assertSame(['State A', 'State B'], $template->getStates()->getValues());
    }

    /**
     * @covers ::getRolePermissions
     */
    public function testRolePermissions(): void
    {
        $template = new Template(new Project());
        self::assertEmpty($template->getRolePermissions());

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($template, 'rolePermissions');
        $permissions->add('Permission A');
        $permissions->add('Permission B');

        self::assertSame(['Permission A', 'Permission B'], $template->getRolePermissions()->getValues());
    }

    /**
     * @covers ::getGroupPermissions
     */
    public function testGroupPermissions(): void
    {
        $template = new Template(new Project());
        self::assertEmpty($template->getGroupPermissions());

        /** @var \Doctrine\Common\Collections\Collection $permissions */
        $permissions = $this->getProperty($template, 'groupPermissions');
        $permissions->add('Permission A');
        $permissions->add('Permission B');

        self::assertSame(['Permission A', 'Permission B'], $template->getGroupPermissions()->getValues());
    }
}
