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

namespace App\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Doctrine\CastFunction
 */
final class CastFunctionTest extends WebTestCase
{
    /**
     * @covers ::getSql
     * @covers ::parse
     */
    public function testCast(): void
    {
        self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $doctrine->getManager();

        $query = $manager->createQuery('
            SELECT DISTINCT
                CAST(3.14159 AS CHAR(2)) AS string,
                CAST(3.14159 AS DECIMAL(5,4)) AS decimal
            FROM App:User u
        ');

        $expected = [
            'string'  => '3.',
            'decimal' => '3.1416',
        ];

        self::assertSame($expected, $query->getSingleResult());
    }
}
