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

namespace App\Message\Issues;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Issues\DeleteIssueCommand
 */
final class DeleteIssueCommandTest extends TestCase
{
    /**
     * @covers ::getIssue
     */
    public function testConstructor(): void
    {
        $command = new DeleteIssueCommand(1);

        self::assertSame(1, $command->getIssue());
    }
}
