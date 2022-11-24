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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\StateGroupTransition;
use App\Entity\StateRoleTransition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'State' entity.
 */
class StateTransitionFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
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
            'task' => [
                SystemRoleEnum::Author->value => [
                    'completed:%s' => 'new:%s',
                ],
                SystemRoleEnum::Responsible->value => [
                    'assigned:%s' => 'completed:%s',
                ],
                'managers:%s' => [
                    'new:%s'       => 'assigned:%s',
                    'assigned:%s'  => 'duplicated:%s',
                    'completed:%s' => 'new:%s',
                ],
            ],

            'issue' => [
                SystemRoleEnum::Author->value => [
                    'submitted:%s' => 'resolved:%s',
                    'opened:%s'    => 'resolved:%s',
                    'resolved:%s'  => 'opened:%s',
                ],
                SystemRoleEnum::Responsible->value => [
                    'opened:%s' => 'resolved:%s',
                ],
                'managers:%s' => [
                    'submitted:%s' => 'opened:%s',
                    'opened:%s'    => 'resolved:%s',
                ],
                'support:%s' => [
                    'submitted:%s' => 'opened:%s',
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $groups) {
                foreach ($groups as $gref => $transitions) {
                    foreach ($transitions as $from => $to) {
                        /** @var \App\Entity\State $fromState */
                        $fromState = $this->getReference(sprintf($from, $pref));

                        /** @var \App\Entity\State $toState */
                        $toState = $this->getReference(sprintf($to, $pref));

                        if (SystemRoleEnum::tryFrom($gref)) {
                            $roleTransition = new StateRoleTransition($fromState, $toState, SystemRoleEnum::from($gref));
                            $manager->persist($roleTransition);
                        } else {
                            /** @var \App\Entity\Group $group */
                            $group = $this->getReference(sprintf($gref, $pref));

                            $groupTransition = new StateGroupTransition($fromState, $toState, $group);
                            $manager->persist($groupTransition);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }
}
