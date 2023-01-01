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

namespace App;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * A trait to authenticate in simulated browser.
 *
 * @method \Symfony\Component\DependencyInjection\ContainerInterface getContainer()
 *
 * @property KernelBrowser $client
 */
trait LoginTrait
{
    /**
     * Authenticates specified user in the simulated browser.
     */
    protected function loginUser(string $email): KernelBrowser
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var \App\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $doctrine->getRepository(User::class);
        $user       = $repository->findOneByEmail($email);

        return $this->client->loginUser($user);
    }
}
