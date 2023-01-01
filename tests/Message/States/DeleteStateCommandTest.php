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

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\States\DeleteStateCommand
 */
final class DeleteStateCommandTest extends TestCase
{
    /**
     * @covers ::getState
     */
    public function testConstructor(): void
    {
        $command = new DeleteStateCommand(1);

        self::assertSame(1, $command->getState());
    }
}
