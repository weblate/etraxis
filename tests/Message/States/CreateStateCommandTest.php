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

namespace App\Message\States;

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\States\CreateStateCommand
 */
final class CreateStateCommandTest extends TestCase
{
    /**
     * @covers ::getName
     * @covers ::getResponsible
     * @covers ::getTemplate
     * @covers ::getType
     */
    public function testConstructor(): void
    {
        $command = new CreateStateCommand(1, 'New', StateTypeEnum::Initial, StateResponsibleEnum::Assign);

        self::assertSame(1, $command->getTemplate());
        self::assertSame('New', $command->getName());
        self::assertSame(StateTypeEnum::Initial, $command->getType());
        self::assertSame(StateResponsibleEnum::Assign, $command->getResponsible());
    }
}
