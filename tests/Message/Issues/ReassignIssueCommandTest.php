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
 * @coversDefaultClass \App\Message\Issues\ReassignIssueCommand
 */
final class ReassignIssueCommandTest extends TestCase
{
    /**
     * @covers ::getIssue
     * @covers ::getResponsible
     */
    public function testConstructor(): void
    {
        $command = new ReassignIssueCommand(1, 2);

        self::assertSame(1, $command->getIssue());
        self::assertSame(2, $command->getResponsible());
    }
}
