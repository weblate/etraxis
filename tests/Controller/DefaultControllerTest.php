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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\DefaultController
 */
final class DefaultControllerTest extends WebTestCase
{
    /**
     * @covers ::index
     */
    public function testHomepageAnonymous(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/');

        self::assertTrue($client->getResponse()->isOk());
    }
}
