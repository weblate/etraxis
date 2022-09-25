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

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'User' entity.
 */
class UserFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * @see DependentFixtureInterface
     */
    public function getDependencies(): array
    {
        return [
            ProductionFixtures::class,
        ];
    }

    /**
     * @see FixtureInterface
     */
    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user
            ->setEmail('artem@example.com')
            ->setPassword('secret')
            ->setFullname('Artem Rodygin')
            ->setAdmin(false)
        ;

        $manager->persist($user);
        $manager->flush();
    }
}
