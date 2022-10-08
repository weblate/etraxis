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

namespace App\Message\States;

use App\Entity\Enums\StateResponsibleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\States\UpdateStateCommand
 */
final class UpdateStateCommandTest extends TestCase
{
    /**
     * @covers ::getName
     * @covers ::getResponsible
     * @covers ::getState
     */
    public function testConstructor(): void
    {
        $command = new UpdateStateCommand(1, 'New', StateResponsibleEnum::Assign);

        self::assertSame(1, $command->getState());
        self::assertSame('New', $command->getName());
        self::assertSame(StateResponsibleEnum::Assign, $command->getResponsible());
    }
}
