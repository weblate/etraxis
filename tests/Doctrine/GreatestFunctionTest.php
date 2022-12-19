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
 * @coversDefaultClass \App\Doctrine\GreatestFunction
 */
final class GreatestFunctionTest extends WebTestCase
{
    /**
     * @covers ::getSql
     * @covers ::parse
     */
    public function testGreatest(): void
    {
        self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $doctrine->getManager();

        /** var \Doctrine\ORM\Query $query */
        $query = $manager->createQuery('
            SELECT DISTINCT
                GREATEST(3, 4) AS simple,
                GREATEST(3, 4, 5) AS last,
                GREATEST(5, 4, 3) AS first,
                GREATEST(4, 5, 3) AS middle,
                GREATEST(3, 4, -5) AS negative
            FROM App:User u
        ');

        $expected = [
            'simple'   => 4,
            'last'     => 5,
            'first'    => 5,
            'middle'   => 5,
            'negative' => 4,
        ];

        self::assertSame($expected, $query->getSingleResult());
    }
}
