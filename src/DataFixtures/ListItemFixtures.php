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

use App\Entity\Field;
use App\Entity\ListItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'ListItem' entity.
 */
class ListItemFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * @see DependentFixtureInterface::getDependencies
     */
    public function getDependencies(): array
    {
        return [
            FieldFixtures::class,
        ];
    }

    /**
     * @see FixtureInterface::load
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            1 => 'high',
            2 => 'normal',
            3 => 'low',
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $value => $text) {
                /** @var Field $field */
                $field = $this->getReference(sprintf('new:%s:priority', $pref));

                $item = new ListItem($field);

                $item
                    ->setValue($value)
                    ->setText($text)
                ;

                $manager->persist($item);
            }
        }

        $manager->flush();
    }
}
