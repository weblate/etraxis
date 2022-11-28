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

use App\Entity\Project;
use App\ReflectionTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Project' entity.
 */
class ProjectFixtures extends Fixture implements FixtureInterface
{
    use ReflectionTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'project:a' => [
                'name'        => 'Distinctio',
                'description' => 'Project A',
                'created'     => '2015-04-15',
                'suspended'   => true,
            ],
            'project:b' => [
                'name'        => 'Molestiae',
                'description' => 'Project B',
                'created'     => '2016-09-10',
            ],
            'project:c' => [
                'name'        => 'Excepturi',
                'description' => 'Project C',
                'created'     => '2017-04-11',
            ],
            'project:d' => [
                'name'        => 'Presto',
                'description' => 'Project D',
                'created'     => '2018-01-12',
            ],
        ];

        date_default_timezone_set('UTC');

        foreach ($data as $ref => $row) {
            $project = new Project();

            $project
                ->setName($row['name'])
                ->setDescription($row['description'])
                ->setSuspended($row['suspended'] ?? false)
            ;

            $this->setProperty($project, 'createdAt', strtotime($row['created']));

            $this->addReference($ref, $project);

            $manager->persist($project);
        }

        $manager->flush();
    }
}
