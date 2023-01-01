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

use App\Entity\LastRead;
use App\ReflectionTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'LastRead' entity.
 */
class LastReadFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    use ReflectionTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            IssueFixtures::class,
            EventFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'fdooley@example.com'    => [
                'task:%s:1' => 'closedAt',
                'task:%s:2' => 'createdAt',
                'task:%s:3' => 'closedAt',
                'task:%s:5' => 'createdAt',
                'task:%s:6' => 'createdAt',
            ],
            'tmarquardt@example.com' => [
                'req:%s:1' => 'closedAt',
                'req:%s:2' => 'createdAt',
                'req:%s:3' => 'createdAt',
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {
            foreach ($data as $uref => $issues) {
                /** @var \App\Entity\User $user */
                $user = $this->getReference('user:'.$uref);

                foreach ($issues as $iref => $property) {
                    /** @var \App\Entity\Issue $issue */
                    $issue = $this->getReference(sprintf($iref, $pref));
                    $manager->refresh($issue);

                    $read = new LastRead($issue, $user);

                    $timestamp = $this->getProperty($issue, $property);
                    $this->setProperty($read, 'readAt', $timestamp);

                    $manager->persist($read);
                }
            }
        }

        $manager->flush();
    }
}
