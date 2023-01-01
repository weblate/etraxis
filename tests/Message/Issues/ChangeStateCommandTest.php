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

namespace App\Message\Issues;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Issues\ChangeStateCommand
 */
final class ChangeStateCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getFields
     * @covers ::getIssue
     * @covers ::getResponsible
     * @covers ::getState
     */
    public function testConstructor(): void
    {
        $command = new ChangeStateCommand(1, 2, null, [1 => 'foo']);

        self::assertSame(1, $command->getIssue());
        self::assertSame(2, $command->getState());
        self::assertNull($command->getResponsible());
        self::assertSame([1 => 'foo'], $command->getFields());
        self::assertSame('foo', $command->getField(1));
        self::assertNull($command->getField(2));
    }
}
