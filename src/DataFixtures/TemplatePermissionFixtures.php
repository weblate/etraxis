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
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\TemplateGroupPermission;
use App\Entity\TemplateRolePermission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for 'Template' entity.
 */
class TemplatePermissionFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            GroupFixtures::class,
            TemplateFixtures::class,
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
                    TemplatePermissionEnum::CreateIssues,
                    TemplatePermissionEnum::EditIssues,
                    TemplatePermissionEnum::ReassignIssues,
                    TemplatePermissionEnum::SuspendIssues,
                    TemplatePermissionEnum::ResumeIssues,
                    TemplatePermissionEnum::DeleteIssues,
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::PrivateComments,
                    TemplatePermissionEnum::AttachFiles,
                    TemplatePermissionEnum::DeleteFiles,
                    TemplatePermissionEnum::ManageDependencies,
                    TemplatePermissionEnum::ManageRelatedIssues,
                ],
                SystemRoleEnum::Responsible->value => [
                    TemplatePermissionEnum::CreateIssues,
                    TemplatePermissionEnum::EditIssues,
                    TemplatePermissionEnum::ReassignIssues,
                    TemplatePermissionEnum::SuspendIssues,
                    TemplatePermissionEnum::ResumeIssues,
                    TemplatePermissionEnum::DeleteIssues,
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::PrivateComments,
                    TemplatePermissionEnum::AttachFiles,
                    TemplatePermissionEnum::DeleteFiles,
                    TemplatePermissionEnum::ManageDependencies,
                    TemplatePermissionEnum::ManageRelatedIssues,
                ],
                'managers:%s' => [
                    TemplatePermissionEnum::ViewIssues,
                    TemplatePermissionEnum::CreateIssues,
                    TemplatePermissionEnum::EditIssues,
                    TemplatePermissionEnum::ReassignIssues,
                    TemplatePermissionEnum::SuspendIssues,
                    TemplatePermissionEnum::ResumeIssues,
                    TemplatePermissionEnum::DeleteIssues,
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::PrivateComments,
                    TemplatePermissionEnum::AttachFiles,
                    TemplatePermissionEnum::DeleteFiles,
                    TemplatePermissionEnum::ManageDependencies,
                    TemplatePermissionEnum::ManageRelatedIssues,
                ],
                'developers:%s' => [
                    TemplatePermissionEnum::ViewIssues,
                    TemplatePermissionEnum::CreateIssues,
                ],
                'support:%s' => [
                    TemplatePermissionEnum::CreateIssues,
                ],
            ],

            'req' => [
                SystemRoleEnum::Author->value => [
                    TemplatePermissionEnum::EditIssues,
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::AttachFiles,
                ],
                SystemRoleEnum::Responsible->value => [
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::AttachFiles,
                    TemplatePermissionEnum::ManageDependencies,
                    TemplatePermissionEnum::ManageRelatedIssues,
                ],
                'managers:%s' => [
                    TemplatePermissionEnum::ViewIssues,
                    TemplatePermissionEnum::CreateIssues,
                    TemplatePermissionEnum::EditIssues,
                    TemplatePermissionEnum::ReassignIssues,
                    TemplatePermissionEnum::SuspendIssues,
                    TemplatePermissionEnum::ResumeIssues,
                    TemplatePermissionEnum::DeleteIssues,
                    TemplatePermissionEnum::AddComments,
                    TemplatePermissionEnum::PrivateComments,
                    TemplatePermissionEnum::AttachFiles,
                    TemplatePermissionEnum::DeleteFiles,
                    TemplatePermissionEnum::ManageDependencies,
                    TemplatePermissionEnum::ManageRelatedIssues,
                ],
                'clients:%s' => [
                    TemplatePermissionEnum::CreateIssues,
                ],
                'support:%s' => [
                    TemplatePermissionEnum::ViewIssues,
                    TemplatePermissionEnum::PrivateComments,
                ],
                'staff' => [
                    TemplatePermissionEnum::ViewIssues,
                ],
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {
            foreach ($data as $tref => $groups) {
                /** @var \App\Entity\Template $template */
                $template = $this->getReference(sprintf('%s:%s', $tref, $pref));

                foreach ($groups as $gref => $permissions) {
                    if (SystemRoleEnum::tryFrom($gref)) {
                        foreach ($permissions as $permission) {
                            $rolePermission = new TemplateRolePermission($template, SystemRoleEnum::from($gref), $permission);
                            $manager->persist($rolePermission);
                        }
                    } else {
                        /** @var \App\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        foreach ($permissions as $permission) {
                            $groupPermission = new TemplateGroupPermission($template, $group, $permission);
                            $manager->persist($groupPermission);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }
}
