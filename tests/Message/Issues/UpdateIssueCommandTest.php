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
 * @coversDefaultClass \App\Message\Issues\UpdateIssueCommand
 */
final class UpdateIssueCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getFields
     * @covers ::getIssue
     * @covers ::getSubject
     */
    public function testConstructor(): void
    {
        $command = new UpdateIssueCommand(1, 'Test issue', [1 => 'foo']);

        self::assertSame(1, $command->getIssue());
        self::assertSame('Test issue', $command->getSubject());
        self::assertSame([1 => 'foo'], $command->getFields());
        self::assertSame('foo', $command->getField(1));
        self::assertNull($command->getField(2));
    }
}
