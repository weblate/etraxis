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

namespace App\Message\Comments;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Comments\AddCommentCommand
 */
final class AddCommentCommandTest extends TestCase
{
    /**
     * @covers ::getBody
     * @covers ::getIssue
     * @covers ::isPrivate
     */
    public function testConstructor(): void
    {
        $command = new AddCommentCommand(1, 'eTraxis', true);

        self::assertSame(1, $command->getIssue());
        self::assertSame('eTraxis', $command->getBody());
        self::assertTrue($command->isPrivate());
    }
}
