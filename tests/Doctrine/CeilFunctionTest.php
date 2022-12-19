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

namespace App\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Doctrine\CeilFunction
 */
final class CeilFunctionTest extends WebTestCase
{
    /**
     * @covers ::getSql
     * @covers ::parse
     */
    public function testCeil(): void
    {
        self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $doctrine->getManager();

        /** var \Doctrine\ORM\Query $query */
        $query = $manager->createQuery('
            SELECT DISTINCT
                CEIL(3) AS posInt,
                CEIL(3.1) AS posLittle,
                CEIL(3.9) AS posBig,
                CEIL(-3) as negInt,
                CEIL(-3.1) as negLittle,
                CEIL(-3.9) as negBig
            FROM App:User u
        ');

        $expected = [
            'posInt'    => 3,
            'posLittle' => 4,
            'posBig'    => 4,
            'negInt'    => -3,
            'negLittle' => -3,
            'negBig'    => -3,
        ];

        self::assertSame($expected, array_map(fn ($entry) => (int) $entry, $query->getSingleResult()));
    }
}
