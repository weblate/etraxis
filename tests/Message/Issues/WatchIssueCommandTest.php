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
 * @coversDefaultClass \App\Message\Issues\WatchIssueCommand
 */
final class WatchIssueCommandTest extends TestCase
{
    /**
     * @covers ::getIssue
     */
    public function testConstructor(): void
    {
        $command = new WatchIssueCommand(1);

        self::assertSame(1, $command->getIssue());
    }
}
