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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\StateGroupTransition
 */
final class StateGroupTransitionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $transition = new StateGroupTransition($from, $to, $group);

        self::assertSame($from, $transition->getFromState());
        self::assertSame($to, $transition->getToState());
        self::assertSame($group, $transition->getGroup());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionStates(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template.');

        $project   = new Project();
        $template1 = new Template($project);
        $template2 = new Template($project);
        $from      = new State($template1, StateTypeEnum::Initial);
        $to        = new State($template2, StateTypeEnum::Intermediate);
        $group     = new Group($project);

        new StateGroupTransition($from, $to, $group);
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
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project2);

        $group->setName('foo');

        new StateGroupTransition($from, $to, $group);
    }

    /**
     * @covers ::getFromState
     */
    public function testFromState(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $transition = new StateGroupTransition($from, $to, $group);
        self::assertSame($from, $transition->getFromState());
    }

    /**
     * @covers ::getToState
     */
    public function testToState(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $transition = new StateGroupTransition($from, $to, $group);
        self::assertSame($to, $transition->getToState());
    }

    /**
     * @covers ::getGroup
     */
    public function testGroup(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);
        $group    = new Group($project);

        $transition = new StateGroupTransition($from, $to, $group);
        self::assertSame($group, $transition->getGroup());
    }
}
