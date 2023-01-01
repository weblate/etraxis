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

use App\Entity\Enums\StateTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\StateResponsibleGroup
 */
final class StateResponsibleGroupTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $responsible = new StateResponsibleGroup($state, $group);

        self::assertSame($state, $responsible->getState());
        self::assertSame($group, $responsible->getGroup());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionGroup(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: foo');

        $project1 = new Project();
        $project2 = new Project();
        $template = new Template($project1);
        $state    = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project2);

        $group->setName('foo');

        new StateResponsibleGroup($state, $group);
    }

    /**
     * @covers ::getState
     */
    public function testState(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $responsible = new StateResponsibleGroup($state, $group);
        self::assertSame($state, $responsible->getState());
    }

    /**
     * @covers ::getGroup
     */
    public function testGroup(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $state    = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $responsible = new StateResponsibleGroup($state, $group);
        self::assertSame($group, $responsible->getGroup());
    }
}
