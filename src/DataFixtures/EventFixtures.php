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

use App\Entity\Dependency;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\SecondsEnum;
use App\Entity\Event;
use App\Entity\RelatedIssue;
use App\Entity\Transition;
use App\ReflectionTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Event' entity.
 */
class EventFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    use ReflectionTrait;
    use UsersTrait;

    // Data structure.
    protected const EVENT_TYPE      = 0;
    protected const EVENT_USER      = 1;
    protected const EVENT_DAY       = 2;
    protected const EVENT_MIN       = 3;
    protected const EVENT_PARAMETER = 4;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            StateFixtures::class,
            IssueFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'task:%s:1' => [
                [EventTypeEnum::IssueCreated,      $this->manager1,   0, 0,  'new'],
                [EventTypeEnum::IssueEdited,       $this->manager1,   0, 5,  null],
                [EventTypeEnum::FileAttached,      $this->manager1,   0, 10, 0],
                [EventTypeEnum::StateChanged,      $this->manager1,   0, 25, 'assigned'],
                [EventTypeEnum::IssueAssigned,     $this->manager1,   0, 25, $this->developer3],
                [EventTypeEnum::IssueReassigned,   $this->manager1,   0, 29, $this->developer1],
                [EventTypeEnum::RelatedIssueAdded, $this->manager1,   0, 30, 'task:%s:2'],
                [EventTypeEnum::PublicComment,     $this->manager1,   1, 0,  null],
                [EventTypeEnum::IssueClosed,       $this->developer1, 3, 0,  'completed'],
            ],

            'task:%s:2' => [
                [EventTypeEnum::IssueCreated,      $this->manager2,   0, 0,   'new'],
                [EventTypeEnum::StateChanged,      $this->manager1,   0, 10,  'assigned'],
                [EventTypeEnum::IssueAssigned,     $this->manager1,   0, 10,  $this->developer3],
                [EventTypeEnum::FileAttached,      $this->manager1,   0, 15,  0],
                [EventTypeEnum::FileAttached,      $this->manager1,   0, 20,  1],
                [EventTypeEnum::PublicComment,     $this->manager1,   1, 0,   null],
                [EventTypeEnum::IssueClosed,       $this->developer3, 2, 35,  'completed'],
                [EventTypeEnum::IssueReopened,     $this->manager2,   2, 90,  'new'],
                [EventTypeEnum::IssueEdited,       $this->manager2,   2, 91,  null],
                [EventTypeEnum::StateChanged,      $this->manager2,   2, 95,  'assigned'],
                [EventTypeEnum::IssueAssigned,     $this->manager2,   2, 95,  $this->developer3],
                [EventTypeEnum::IssueEdited,       $this->manager2,   2, 96,  null],
                [EventTypeEnum::FileDeleted,       $this->manager2,   2, 105, 1],
                [EventTypeEnum::PrivateComment,    $this->manager2,   2, 110, null],
                [EventTypeEnum::RelatedIssueAdded, $this->manager2,   2, 115, 'task:%s:3'],
                [EventTypeEnum::FileAttached,      $this->developer3, 3, 60,  2],
                [EventTypeEnum::PublicComment,     $this->developer3, 3, 65,  null],
            ],

            'task:%s:3' => [
                [EventTypeEnum::IssueCreated,  $this->manager3,   0, 0, 'new'],
                [EventTypeEnum::StateChanged,  $this->manager3,   0, 5, 'assigned'],
                [EventTypeEnum::IssueAssigned, $this->manager3,   0, 5, $this->developer1],
                [EventTypeEnum::IssueClosed,   $this->developer1, 5, 0, 'completed'],
            ],

            'task:%s:4' => [
                [EventTypeEnum::IssueCreated,  $this->developer1, 0, 0,   'new'],
                [EventTypeEnum::IssueClosed,   $this->manager2,   0, 135, 'duplicated'],
            ],

            'task:%s:5' => [
                [EventTypeEnum::IssueCreated,  $this->manager3, 0, 0, 'new'],
            ],

            'task:%s:6' => [
                [EventTypeEnum::IssueCreated,  $this->manager3, 0, 0, 'new'],
            ],

            'task:%s:7' => [
                [EventTypeEnum::IssueCreated,  $this->developer2, 0, 0, 'new'],
                [EventTypeEnum::StateChanged,  $this->manager2,   1, 0, 'assigned'],
                [EventTypeEnum::IssueAssigned, $this->manager2,   1, 0, $this->developer2],
                [EventTypeEnum::IssueClosed,   $this->manager3,   2, 0, 'duplicated'],
            ],

            'task:%s:8' => [
                [EventTypeEnum::IssueCreated,      $this->developer2, 0, 0,  'new'],
                [EventTypeEnum::StateChanged,      $this->manager1,   3, 0,  'assigned'],
                [EventTypeEnum::IssueAssigned,     $this->manager1,   3, 0,  $this->developer2],
                [EventTypeEnum::DependencyAdded,   $this->manager1,   3, 5,  'task:%s:3'],
                [EventTypeEnum::RelatedIssueAdded, $this->manager1,   3, 10, 'task:%s:2'],
            ],

            'req:%s:1'  => [
                [EventTypeEnum::IssueCreated,  $this->client1,  0, 0, 'submitted'],
                [EventTypeEnum::StateChanged,  $this->manager1, 0, 5, 'opened'],
                [EventTypeEnum::IssueAssigned, $this->manager1, 0, 5, $this->support1],
                [EventTypeEnum::IssueClosed,   $this->support1, 2, 0, 'resolved'],
            ],

            'req:%s:2'  => [
                [EventTypeEnum::IssueCreated,    $this->client2,  0, 0,  'submitted'],
                [EventTypeEnum::StateChanged,    $this->support2, 0, 5,  'opened'],
                [EventTypeEnum::IssueAssigned,   $this->support2, 0, 5,  $this->support2],
                [EventTypeEnum::DependencyAdded, $this->support2, 0, 10, 'req:%s:3'],
            ],

            'req:%s:3'  => [
                [EventTypeEnum::IssueCreated,  $this->client2,  0, 0, 'submitted'],
                [EventTypeEnum::StateChanged,  $this->support2, 0, 5, 'opened'],
                [EventTypeEnum::IssueAssigned, $this->support2, 0, 5, $this->support2],
                [EventTypeEnum::IssueClosed,   $this->support2, 2, 0, 'resolved'],
            ],

            'req:%s:4'  => [
                [EventTypeEnum::IssueCreated,  $this->client3,  0, 0, 'submitted'],
                [EventTypeEnum::StateChanged,  $this->manager2, 1, 0, 'opened'],
                [EventTypeEnum::IssueAssigned, $this->manager2, 1, 0, $this->support1],
            ],

            'req:%s:5'  => [
                [EventTypeEnum::IssueCreated,    $this->client2,  0, 0, 'submitted'],
                [EventTypeEnum::StateChanged,    $this->support3, 0, 5, 'opened'],
                [EventTypeEnum::IssueAssigned,   $this->support3, 0, 5, $this->support3],
                [EventTypeEnum::DependencyAdded, $this->support3, 1, 0, 'req:%s:2'],
            ],

            'req:%s:6'  => [
                [EventTypeEnum::IssueCreated,    $this->client1,  0, 0, 'submitted'],
                [EventTypeEnum::DependencyAdded, $this->manager2, 0, 5, 'req:%s:1'],
                [EventTypeEnum::DependencyAdded, $this->manager1, 2, 0, 'task:%s:8'],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {
            foreach ($data as $iref => $events) {
                /** @var \App\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $index => $row) {
                    /** @var \App\Entity\User $user */
                    $user = $this->getReference($row[self::EVENT_USER][$pref]);

                    $timestamp = $issue->getCreatedAt()
                        + $row[self::EVENT_DAY] * SecondsEnum::OneDay->value
                        + $row[self::EVENT_MIN] * SecondsEnum::OneMinute->value
                        + $index;

                    $event = new Event($issue, $user, $row[self::EVENT_TYPE]);

                    $this->setProperty($event, 'createdAt', $timestamp);
                    $this->setProperty($issue, 'changedAt', $timestamp);

                    switch ($row[self::EVENT_TYPE]) {
                        case EventTypeEnum::IssueCreated:
                        case EventTypeEnum::IssueReopened:
                        case EventTypeEnum::IssueClosed:
                        case EventTypeEnum::StateChanged:
                            /** @var \App\Entity\State $state */
                            $state = $this->getReference(sprintf('%s:%s', $row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $state->getName());

                            $issue->setState($state);

                            if ($state->isFinal()) {
                                $this->setProperty($issue, 'closedAt', $timestamp);
                            }

                            $transition = new Transition($event, $state);

                            $manager->persist($transition);

                            break;

                        case EventTypeEnum::IssueAssigned:
                        case EventTypeEnum::IssueReassigned:
                            /** @var \App\Entity\User $user */
                            $user = $this->getReference($row[self::EVENT_PARAMETER][$pref]);
                            $this->setProperty($event, 'parameter', $user->getFullname());

                            break;

                        case EventTypeEnum::DependencyAdded:
                            /** @var \App\Entity\Issue $issue2 */
                            $issue2 = $this->getReference(sprintf($row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $issue2->getFullId());

                            $dependency = new Dependency($event, $issue2);

                            $manager->persist($dependency);

                            break;

                        case EventTypeEnum::RelatedIssueAdded:
                            /** @var \App\Entity\Issue $issue2 */
                            $issue2 = $this->getReference(sprintf($row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $issue2->getFullId());

                            $dependency = new RelatedIssue($event, $issue2);

                            $manager->persist($dependency);

                            break;

                        case EventTypeEnum::DependencyRemoved:
                        case EventTypeEnum::RelatedIssueRemoved:
                            /** @var \App\Entity\Issue $issue2 */
                            $issue2 = $this->getReference(sprintf($row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $issue2->getFullId());

                            break;

                        default:
                            $this->setProperty($event, 'parameter', $row[self::EVENT_PARAMETER]);
                    }

                    $this->addReference(sprintf('%s:event:%s', sprintf($iref, $pref), $index), $event);

                    $manager->persist($event);
                }

                $manager->persist($issue);
            }
        }

        $manager->flush();
    }
}
