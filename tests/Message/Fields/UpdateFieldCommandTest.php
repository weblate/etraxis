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

namespace App\Message\Fields;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Fields\UpdateFieldCommand
 */
final class UpdateFieldCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getField
     * @covers ::getName
     * @covers ::getParameters
     * @covers ::isRequired
     */
    public function testConstructor(): void
    {
        $command = new UpdateFieldCommand(1, 'Priority', null, true, ['foo' => 'bar']);

        self::assertSame(1, $command->getField());
        self::assertSame('Priority', $command->getName());
        self::assertNull($command->getDescription());
        self::assertTrue($command->isRequired());
        self::assertSame(['foo' => 'bar'], $command->getParameters());
    }
}
