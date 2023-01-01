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
 * @coversDefaultClass \App\Message\Issues\SuspendIssueCommand
 */
final class SuspendIssueCommandTest extends TestCase
{
    /**
     * @covers ::getDate
     * @covers ::getIssue
     */
    public function testConstructor(): void
    {
        $command = new SuspendIssueCommand(1, '1995-11-22');

        self::assertSame(1, $command->getIssue());
        self::assertSame('1995-11-22', $command->getDate());
    }
}
