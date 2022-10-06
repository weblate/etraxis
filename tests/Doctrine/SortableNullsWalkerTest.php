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

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Doctrine\SortableNullsWalker
 */
final class SortableNullsWalkerTest extends WebTestCase
{
    /**
     * @covers ::walkOrderByItem
     */
    public function testAsc(): void
    {
        self::createClient();

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(User::class);

        $users = $repository
            ->createQueryBuilder('user')
            ->orderBy('user.description', 'ASC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->execute()
        ;

        $expected = [
            'artem@example.com',            // the description is NULL here
            'einstein@ldap.forumsys.com',   // the description is NULL here
            'admin@example.com',
        ];

        $actual = array_map(fn (User $user) => $user->getEmail(), $users);

        self::assertSame($expected, array_slice($actual, 0, 3));
    }

    /**
     * @covers ::walkOrderByItem
     */
    public function testDesc(): void
    {
        self::createClient();

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(User::class);

        $users = $repository
            ->createQueryBuilder('user')
            ->orderBy('user.description', 'DESC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->execute()
        ;

        $expected = [
            'admin@example.com',
            'artem@example.com',            // the description is NULL here
            'einstein@ldap.forumsys.com',   // the description is NULL here
        ];

        $actual = array_map(fn (User $user) => $user->getEmail(), $users);

        self::assertSame($expected, array_slice($actual, -3));
    }
}
