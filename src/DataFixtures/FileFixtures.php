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

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\File;
use App\ReflectionTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'File' entity.
 */
class FileFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    use ReflectionTrait;

    // Data structure.
    protected const FILE_NAME    = 0;
    protected const FILE_SIZE    = 1;
    protected const FILE_TYPE    = 2;
    protected const FILE_REMOVED = 3;

    /**
     * @see DependentFixtureInterface::getDependencies
     */
    public function getDependencies(): array
    {
        return [
            IssueFixtures::class,
            EventFixtures::class,
        ];
    }

    /**
     * @see FixtureInterface::load
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'task:%s:1' => [
                [
                    'Inventore.pdf',
                    175971,     // 171.85 KB
                    'application/pdf',
                    false,
                ],
            ],

            'task:%s:2' => [
                [
                    'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
                    217948,     // 212.84 KB
                    'application/vnd.ms-word',
                    false,
                ],
                [
                    'Possimus sapiente.pdf',
                    10753,      // 10.50 KB
                    'application/pdf',
                    true,
                ],
                [
                    'Nesciunt nulla sint amet.xslx',
                    6037279,    // 5895.78 KB
                    'application/vnd.ms-excel',
                    false,
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {
            foreach ($data as $iref => $files) {
                /** @var \App\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                /** @var Event[] $events */
                $events = $manager->getRepository(Event::class)->findBy([
                    'type'  => EventTypeEnum::FileAttached,
                    'issue' => $issue,
                ], [
                    'id' => 'ASC',
                ]);

                foreach ($files as $index => $row) {
                    $file = new File($events[$index], $row[self::FILE_NAME], $row[self::FILE_SIZE], $row[self::FILE_TYPE]);

                    $manager->persist($file);
                    $manager->flush();

                    if ($row[self::FILE_REMOVED]) {
                        /** @var Event $event */
                        $event = $manager->getRepository(Event::class)->findOneBy([
                            'type'      => EventTypeEnum::FileDeleted,
                            'issue'     => $issue,
                            'parameter' => $index,
                        ]);

                        $this->setProperty($event, 'parameter', $file->getFileName());
                        $this->setProperty($file, 'removedAt', $event->getCreatedAt());

                        $manager->persist($event);
                    }

                    $this->setProperty($events[$index], 'parameter', $file->getFileName());
                    $manager->persist($events[$index]);
                    $manager->flush();
                }
            }
        }
    }
}
