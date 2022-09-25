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

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\DefaultAdminController
 */
final class DefaultAdminControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testIndexAnonymous(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/admin');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::index
     */
    public function testIndexUser(): void
    {
        $client = self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $client->loginUser($user);
        $client->request(Request::METHOD_GET, '/admin');

        self::assertTrue($client->getResponse()->isForbidden());
    }

    /**
     * @covers ::index
     */
    public function testIndexAdmin(): void
    {
        $client = self::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        $client->loginUser($user);
        $client->request(Request::METHOD_GET, '/admin');

        self::assertTrue($client->getResponse()->isOk());
    }
}
