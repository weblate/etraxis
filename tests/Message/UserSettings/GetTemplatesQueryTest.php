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

namespace App\Message\UserSettings;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\UserSettings\GetTemplatesQuery
 */
final class GetTemplatesQueryTest extends TestCase
{
    /**
     * @covers ::getUser
     */
    public function testConstructor(): void
    {
        $command = new GetTemplatesQuery(1);

        self::assertSame(1, $command->getUser());
    }
}
