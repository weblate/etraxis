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

use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Field;
use App\Entity\FieldValue;
use App\Entity\TextValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * @see DependentFixtureInterface::getDependencies
     */
    public function getDependencies(): array
    {
        return [
            StateFixtures::class,
        ];
    }

    /**
     * @see FixtureInterface::load
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'new'        => [
                [
                    'type'       => FieldTypeEnum::List,
                    'name'       => 'Priority',
                    'required'   => true,
                    'position'   => 1,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::DEFAULT, 2)
                        ;
                    },
                ],
                [
                    'type'       => FieldTypeEnum::Text,
                    'name'       => 'Description',
                    'required'   => false,
                    'position'   => 2,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::LENGTH, TextValue::MAX_VALUE)
                            ->setParameter(Field::DEFAULT, 'How to reproduce:')
                        ;
                    },
                ],
                [
                    'type'     => FieldTypeEnum::Checkbox,
                    'name'     => 'Error',
                    'required' => false,
                    'position' => 3,
                    'deleted'  => true,
                ],
                [
                    'type'       => FieldTypeEnum::Checkbox,
                    'name'       => 'New feature',
                    'required'   => false,
                    'position'   => 3,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::DEFAULT, true)
                        ;
                    },
                ],
            ],

            'assigned'   => [
                [
                    'type'       => FieldTypeEnum::Date,
                    'name'       => 'Due date',
                    'required'   => false,
                    'position'   => 1,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::MINIMUM, 0)
                            ->setParameter(Field::MAXIMUM, 14)
                            ->setParameter(Field::DEFAULT, 14)
                        ;
                    },
                ],
            ],

            'completed'  => [
                [
                    'type'       => FieldTypeEnum::String,
                    'name'       => 'Commit ID',
                    'required'   => false,
                    'position'   => 1,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::LENGTH, 40)
                            ->setParameter(Field::DEFAULT, 'Git commit ID')
                        ;
                    },
                ],
                [
                    'type'        => FieldTypeEnum::Number,
                    'name'        => 'Delta',
                    'description' => 'NCLOC',
                    'required'    => true,
                    'position'    => 2,
                    'parameters'  => function (Field $field): void {
                        $field
                            ->setParameter(Field::MINIMUM, 0)
                            ->setParameter(Field::MAXIMUM, FieldValue::MAX_NUMBER_VALUE)
                        ;
                    },
                ],
                [
                    'type'        => FieldTypeEnum::Duration,
                    'name'        => 'Effort',
                    'description' => 'HH:MM',
                    'required'    => true,
                    'position'    => 3,
                    'parameters'  => function (Field $field): void {
                        $field
                            ->setParameter(Field::MINIMUM, '0:01')
                            ->setParameter(Field::MAXIMUM, '160:00')
                            ->setParameter(Field::DEFAULT, '8:00')
                        ;
                    },
                ],
                [
                    'type'       => FieldTypeEnum::Decimal,
                    'name'       => 'Test coverage',
                    'required'   => false,
                    'position'   => 4,
                    'parameters' => function (Field $field): void {
                        $field
                            ->setParameter(Field::MINIMUM, '0')
                            ->setParameter(Field::MAXIMUM, '100')
                        ;
                    },
                ],
            ],

            'duplicated' => [
                [
                    'type'     => FieldTypeEnum::Issue,
                    'name'     => 'Task ID',
                    'required' => true,
                    'position' => 1,
                    'deleted'  => true,
                ],
                [
                    'type'     => FieldTypeEnum::Issue,
                    'name'     => 'Issue ID',
                    'required' => true,
                    'position' => 1,
                ],
            ],

            'submitted'  => [
                [
                    'type'       => FieldTypeEnum::Text,
                    'name'       => 'Details',
                    'required'   => true,
                    'position'   => 1,
                    'parameters' => function (Field $field): void {
                        $field->setParameter(Field::LENGTH, 250);
                    },
                ],
            ],

            'opened'     => [],

            'resolved'   => [],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $sref => $fields) {
                /** @var \App\Entity\State $state */
                $state = $this->getReference(sprintf('%s:%s', $sref, $pref));

                foreach ($fields as $row) {
                    $field = new Field($state, $row['type']);

                    $field
                        ->setName($row['name'])
                        ->setDescription($row['description'] ?? null)
                        ->setPosition($row['position'])
                        ->setRequired($row['required'])
                    ;

                    if ($row['parameters'] ?? false) {
                        $row['parameters']($field);
                    }

                    if ($row['deleted'] ?? false) {
                        $field->remove();
                    }

                    $this->addReference(sprintf('%s:%s:%s', $sref, $pref, mb_strtolower($row['name'])), $field);

                    $manager->persist($field);
                }
            }
        }

        $manager->flush();
    }
}
