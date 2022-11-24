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

use App\Entity\Template;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Template' entity.
 */
class TemplateFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'a' => ['task' => false, 'req' => true],
            'b' => ['task' => true,  'req' => true],
            'c' => ['task' => false, 'req' => false],
            'd' => ['task' => true,  'req' => false],
        ];

        foreach ($data as $ref => $isLocked) {
            /** @var \App\Entity\Project $project */
            $project = $this->getReference('project:'.$ref);

            $development = new Template($project);
            $support     = new Template($project);

            $development
                ->setName('Development')
                ->setPrefix('task')
                ->setDescription('Development Task '.strtoupper($ref))
                ->setLocked($isLocked['task'])
            ;

            $support
                ->setName('Support')
                ->setPrefix('req')
                ->setDescription('Support Request '.mb_strtoupper($ref))
                ->setCriticalAge(3)
                ->setFrozenTime(7)
                ->setLocked($isLocked['req'])
            ;

            $this->addReference('task:'.$ref, $development);
            $this->addReference('req:'.$ref, $support);

            $manager->persist($development);
            $manager->persist($support);
        }

        $manager->flush();
    }
}
