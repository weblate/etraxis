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

namespace App\Message\Fields;

use App\Entity\Enums\FieldTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Fields\CreateFieldCommand
 */
final class CreateFieldCommandTest extends TestCase
{
    /**
     * @covers ::getDescription
     * @covers ::getName
     * @covers ::getParameters
     * @covers ::getState
     * @covers ::getType
     * @covers ::isRequired
     */
    public function testConstructor(): void
    {
        $command = new CreateFieldCommand(1, 'Priority', FieldTypeEnum::List, null, true, ['foo' => 'bar']);

        self::assertSame(1, $command->getState());
        self::assertSame('Priority', $command->getName());
        self::assertSame(FieldTypeEnum::List, $command->getType());
        self::assertNull($command->getDescription());
        self::assertTrue($command->isRequired());
        self::assertSame(['foo' => 'bar'], $command->getParameters());
    }
}
