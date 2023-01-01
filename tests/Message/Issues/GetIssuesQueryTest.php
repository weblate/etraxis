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

use App\Message\AbstractCollectionQuery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Issues\GetIssuesQuery
 */
final class GetIssuesQueryTest extends TestCase
{
    /**
     * @covers ::clearLimit
     */
    public function testClearLimit(): void
    {
        $query = new GetIssuesQuery(0, AbstractCollectionQuery::MAX_LIMIT);
        self::assertSame(100, $query->getLimit());

        $query->clearLimit();
        self::assertSame(0, $query->getLimit());
    }
}
