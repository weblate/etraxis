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

namespace App\Message\Dependencies;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Dependencies\AddDependencyCommand
 */
final class AddDependencyCommandTest extends TestCase
{
    /**
     * @covers ::getDependency
     * @covers ::getIssue
     */
    public function testConstructor(): void
    {
        $command = new AddDependencyCommand(1, 2);

        self::assertSame(1, $command->getIssue());
        self::assertSame(2, $command->getDependency());
    }
}
