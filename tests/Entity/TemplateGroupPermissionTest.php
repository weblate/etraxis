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

namespace App\Entity;

use App\Entity\Enums\TemplatePermissionEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\TemplateGroupPermission
 */
final class TemplateGroupPermissionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $group    = new Group($project);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermissionEnum::EditIssues);

        self::assertSame($template, $permission->getTemplate());
        self::assertSame($group, $permission->getGroup());
        self::assertSame(TemplatePermissionEnum::EditIssues, $permission->getPermission());
    }

    /**
     * @covers ::getTemplate
     */
    public function testTemplate(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $group    = new Group($project);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermissionEnum::EditIssues);
        self::assertSame($template, $permission->getTemplate());
    }

    /**
     * @covers ::getGroup
     */
    public function testGroup(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $group    = new Group($project);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermissionEnum::EditIssues);
        self::assertSame($group, $permission->getGroup());
    }

    /**
     * @covers ::getPermission
     */
    public function testPermission(): void
    {
        $project  = new Project();
        $template = new Template($project);
        $group    = new Group($project);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermissionEnum::EditIssues);
        self::assertSame(TemplatePermissionEnum::EditIssues, $permission->getPermission());
    }
}
