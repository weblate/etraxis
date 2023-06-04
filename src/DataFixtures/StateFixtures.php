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

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'State' entity.
 */
class StateFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * @see DependentFixtureInterface::getDependencies
     */
    public function getDependencies(): array
    {
        return [
            TemplateFixtures::class,
        ];
    }

    /**
     * @see FixtureInterface::load
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'task' => [
                'Assigned'   => [
                    'type'        => StateTypeEnum::Intermediate,
                    'responsible' => StateResponsibleEnum::Assign,
                ],
                'New'        => [
                    'type'        => StateTypeEnum::Initial,
                    'responsible' => StateResponsibleEnum::Remove,
                ],
                'Completed'  => [
                    'type' => StateTypeEnum::Final,
                ],
                'Duplicated' => [
                    'type' => StateTypeEnum::Final,
                ],
            ],

            'req'  => [
                'Submitted' => [
                    'type'        => StateTypeEnum::Initial,
                    'responsible' => StateResponsibleEnum::Keep,
                ],
                'Opened'    => [
                    'type'        => StateTypeEnum::Intermediate,
                    'responsible' => StateResponsibleEnum::Assign,
                ],
                'Resolved'  => [
                    'type' => StateTypeEnum::Final,
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $tref => $states) {
                /** @var \App\Entity\Template $template */
                $template = $this->getReference(sprintf('%s:%s', $tref, $pref));

                foreach ($states as $name => $row) {
                    $state = new State($template, 'd' === $pref ? StateTypeEnum::Intermediate : $row['type']);

                    $state
                        ->setName($name)
                        ->setResponsible($row['responsible'] ?? StateResponsibleEnum::Remove)
                    ;

                    $this->addReference(sprintf('%s:%s', mb_strtolower($name), $pref), $state);

                    $manager->persist($state);
                }
            }
        }

        $manager->flush();
    }
}
