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

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\FieldGroupPermission;
use App\Entity\FieldRolePermission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldPermissionFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            GroupFixtures::class,
            FieldFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'new:%s:priority'            => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadOnly,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'developers:%s'               => FieldPermissionEnum::ReadOnly,
            ],
            'new:%s:description'         => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadAndWrite,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'developers:%s'               => FieldPermissionEnum::ReadOnly,
            ],
            'new:%s:error'               => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadAndWrite,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'developers:%s'               => FieldPermissionEnum::ReadOnly,
            ],
            'new:%s:new feature'         => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadAndWrite,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'developers:%s'               => FieldPermissionEnum::ReadOnly,
            ],
            'assigned:%s:due date'       => [
                SystemRoleEnum::Responsible->value => FieldPermissionEnum::ReadOnly,
                'managers:%s'                      => FieldPermissionEnum::ReadAndWrite,
            ],
            'completed:%s:commit id'     => [
                'managers:%s'   => FieldPermissionEnum::ReadAndWrite,
                'developers:%s' => FieldPermissionEnum::ReadAndWrite,
            ],
            'completed:%s:delta'         => [
                'managers:%s'   => FieldPermissionEnum::ReadAndWrite,
                'developers:%s' => FieldPermissionEnum::ReadAndWrite,
            ],
            'completed:%s:effort'        => [
                'managers:%s'   => FieldPermissionEnum::ReadAndWrite,
                'developers:%s' => FieldPermissionEnum::ReadAndWrite,
            ],
            'completed:%s:test coverage' => [
                'managers:%s'   => FieldPermissionEnum::ReadAndWrite,
                'developers:%s' => FieldPermissionEnum::ReadAndWrite,
            ],
            'duplicated:%s:task id'      => [
                'managers:%s'   => FieldPermissionEnum::ReadAndWrite,
                'developers:%s' => FieldPermissionEnum::ReadOnly,
            ],
            'duplicated:%s:issue id'     => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadOnly,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'developers:%s'               => FieldPermissionEnum::ReadOnly,
            ],
            'submitted:%s:details'       => [
                SystemRoleEnum::Author->value => FieldPermissionEnum::ReadAndWrite,
                'managers:%s'                 => FieldPermissionEnum::ReadAndWrite,
                'support:%s'                  => FieldPermissionEnum::ReadOnly,
                'staff'                       => FieldPermissionEnum::ReadOnly,
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $fref => $groups) {
                /** @var \App\Entity\Field $field */
                $field = $this->getReference(sprintf($fref, $pref));

                foreach ($groups as $gref => $permission) {
                    if (SystemRoleEnum::tryFrom($gref)) {
                        $rolePermission = new FieldRolePermission($field, SystemRoleEnum::from($gref), $permission);
                        $manager->persist($rolePermission);
                    } else {
                        /** @var \App\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        $groupPermission = new FieldGroupPermission($field, $group, $permission);
                        $manager->persist($groupPermission);
                    }
                }
            }
        }

        $manager->flush();
    }
}
