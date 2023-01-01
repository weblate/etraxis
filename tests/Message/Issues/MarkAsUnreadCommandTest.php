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
 * @coversDefaultClass \App\Message\Issues\MarkAsUnreadCommand
 */
final class MarkAsUnreadCommandTest extends TestCase
{
    /**
     * @covers ::getIssues
     */
    public function testConstructor(): void
    {
        $issues = [1, 2, 3];

        $command = new MarkAsUnreadCommand($issues);

        self::assertSame($issues, $command->getIssues());
    }
}
