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
 * @coversDefaultClass \App\Message\Issues\CreateIssueCommand
 */
final class CreateIssueCommandTest extends TestCase
{
    /**
     * @covers ::getField
     * @covers ::getFields
     * @covers ::getResponsible
     * @covers ::getSubject
     * @covers ::getTemplate
     * @covers ::getTime
     */
    public function testConstructor(): void
    {
        $command = new CreateIssueCommand(1, 'Test issue', null, [1 => 'foo']);

        self::assertSame(1, $command->getTemplate());
        self::assertSame('Test issue', $command->getSubject());
        self::assertNull($command->getResponsible());
        self::assertLessThanOrEqual(2, time() - $command->getTime());
        self::assertSame([1 => 'foo'], $command->getFields());
        self::assertSame('foo', $command->getField(1));
        self::assertNull($command->getField(2));
    }
}
