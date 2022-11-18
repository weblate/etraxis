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

namespace App\Message\Issues;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Issues\CloneIssueCommand
 */
final class CloneIssueCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getFields
     * @covers ::getIssue
     * @covers ::getResponsible
     * @covers ::getSubject
     * @covers ::getTime
     */
    public function testConstructor(): void
    {
        $command = new CloneIssueCommand(1, 'Test issue', null, [1 => 'foo']);

        self::assertSame(1, $command->getIssue());
        self::assertSame('Test issue', $command->getSubject());
        self::assertNull($command->getResponsible());
        self::assertLessThanOrEqual(2, time() - $command->getTime());
        self::assertSame([1 => 'foo'], $command->getFields());
        self::assertSame('foo', $command->getField(1));
        self::assertNull($command->getField(2));
    }
}
