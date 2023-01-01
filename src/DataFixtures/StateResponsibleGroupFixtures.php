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

namespace App\DataFixtures;

use App\Entity\StateResponsibleGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'State' entity.
 */
class StateResponsibleGroupFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            GroupFixtures::class,
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'task'  => [
                'assigned:%s' => [
                    'developers:%s',
                ],
            ],
            'issue' => [
                'opened:%s' => [
                    'support:%s',
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $states) {
                foreach ($states as $sref => $groups) {
                    foreach ($groups as $gref) {
                        /** @var \App\Entity\State $state */
                        $state = $this->getReference(sprintf($sref, $pref));

                        /** @var \App\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        $responsibleGroup = new StateResponsibleGroup($state, $group);
                        $manager->persist($responsibleGroup);
                    }
                }
            }
        }

        $manager->flush();
    }
}
