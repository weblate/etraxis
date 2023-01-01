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

use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\Project
 */
final class ProjectTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project = new Project();

        self::assertLessThanOrEqual(2, time() - $project->getCreatedAt());
        self::assertFalse($project->isSuspended());
        self::assertEmpty($project->getGroups());
        self::assertEmpty($project->getTemplates());
    }

    /**
     * @covers ::getId
     */
    public function testId(): void
    {
        $project = new Project();

        $this->setProperty($project, 'id', 1);
        self::assertSame(1, $project->getId());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName(): void
    {
        $project = new Project();

        $project->setName('eTraxis');
        self::assertSame('eTraxis', $project->getName());
    }

    /**
     * @covers ::getDescription
     * @covers ::setDescription
     */
    public function testDescription(): void
    {
        $project = new Project();
        self::assertNull($project->getDescription());

        $project->setDescription('Lorem Ipsum');
        self::assertSame('Lorem Ipsum', $project->getDescription());
    }

    /**
     * @covers ::getCreatedAt
     */
    public function testCreatedAt(): void
    {
        $project = new Project();

        self::assertLessThanOrEqual(2, time() - $project->getCreatedAt());
    }

    /**
     * @covers ::isSuspended
     * @covers ::setSuspended
     */
    public function testSuspended(): void
    {
        $project = new Project();
        self::assertFalse($project->isSuspended());

        $project->setSuspended(true);
        self::assertTrue($project->isSuspended());

        $project->setSuspended(false);
        self::assertFalse($project->isSuspended());
    }

    /**
     * @covers ::getGroups
     */
    public function testGroups(): void
    {
        $project = new Project();
        self::assertEmpty($project->getGroups());

        /** @var \Doctrine\Common\Collections\Collection $groups */
        $groups = $this->getProperty($project, 'groups');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $project->getGroups()->getValues());
    }

    /**
     * @covers ::getTemplates
     */
    public function testTemplates(): void
    {
        $project = new Project();
        self::assertEmpty($project->getTemplates());

        /** @var \Doctrine\Common\Collections\Collection $templates */
        $templates = $this->getProperty($project, 'templates');
        $templates->add('Template A');
        $templates->add('Template B');

        self::assertSame(['Template A', 'Template B'], $project->getTemplates()->getValues());
    }
}
