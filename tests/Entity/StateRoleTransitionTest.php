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
use App\Entity\Enums\SystemRoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\StateRoleTransition
 */
final class StateRoleTransitionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $template = new Template(new Project());
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);

        $transition = new StateRoleTransition($from, $to, SystemRoleEnum::Author);

        self::assertSame($from, $transition->getFromState());
        self::assertSame($to, $transition->getToState());
        self::assertSame(SystemRoleEnum::Author, $transition->getRole());
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

        new StateRoleTransition($from, $to, SystemRoleEnum::Author);
    }

    /**
     * @covers ::getFromState
     */
    public function testFromState(): void
    {
        $template = new Template(new Project());
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);

        $transition = new StateRoleTransition($from, $to, SystemRoleEnum::Author);
        self::assertSame($from, $transition->getFromState());
    }

    /**
     * @covers ::getToState
     */
    public function testToState(): void
    {
        $template = new Template(new Project());
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);

        $transition = new StateRoleTransition($from, $to, SystemRoleEnum::Author);
        self::assertSame($to, $transition->getToState());
    }

    /**
     * @covers ::getRole
     */
    public function testRole(): void
    {
        $template = new Template(new Project());
        $from     = new State($template, StateTypeEnum::Initial);
        $to       = new State($template, StateTypeEnum::Intermediate);

        $transition = new StateRoleTransition($from, $to, SystemRoleEnum::Author);
        self::assertSame(SystemRoleEnum::Author, $transition->getRole());
    }
}
