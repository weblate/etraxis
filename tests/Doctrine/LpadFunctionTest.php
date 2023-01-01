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
 * @coversDefaultClass \App\Doctrine\LpadFunction
 */
final class LpadFunctionTest extends WebTestCase
{
    /**
     * @covers ::getSql
     * @covers ::parse
     */
    public function testLpad(): void
    {
        self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $doctrine->getManager();

        /** var \Doctrine\ORM\Query $query */
        $query = $manager->createQuery("
            SELECT DISTINCT
                LPAD('', 5, '*') AS emptyToFive,
                LPAD('', 0, '*') AS emptyToZero,
                LPAD('123', 5, '0') AS numberToLonger,
                LPAD('123', 2, '0') AS numberToShorter
            FROM App:User u
        ");

        $expected = [
            'emptyToFive'     => '*****',
            'emptyToZero'     => '',
            'numberToLonger'  => '00123',
            'numberToShorter' => '12',
        ];

        self::assertSame($expected, $query->getSingleResult());
    }
}
