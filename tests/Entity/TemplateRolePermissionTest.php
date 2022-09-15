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

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\TemplateRolePermission
 */
final class TemplateRolePermissionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $project  = new Project();
        $template = new Template($project);

        $permission = new TemplateRolePermission($template, SystemRoleEnum::Author, TemplatePermissionEnum::EditIssues);

        self::assertSame($template, $permission->getTemplate());
        self::assertSame(SystemRoleEnum::Author, $permission->getRole());
        self::assertSame(TemplatePermissionEnum::EditIssues, $permission->getPermission());
    }

    /**
     * @covers ::getTemplate
     */
    public function testTemplate(): void
    {
        $template = new Template(new Project());

        $permission = new TemplateRolePermission($template, SystemRoleEnum::Author, TemplatePermissionEnum::EditIssues);
        self::assertSame($template, $permission->getTemplate());
    }

    /**
     * @covers ::getRole
     */
    public function testRole(): void
    {
        $template = new Template(new Project());

        $permission = new TemplateRolePermission($template, SystemRoleEnum::Author, TemplatePermissionEnum::EditIssues);
        self::assertSame(SystemRoleEnum::Author, $permission->getRole());
    }

    /**
     * @covers ::getPermission
     */
    public function testPermission(): void
    {
        $template = new Template(new Project());

        $permission = new TemplateRolePermission($template, SystemRoleEnum::Author, TemplatePermissionEnum::EditIssues);
        self::assertSame(TemplatePermissionEnum::EditIssues, $permission->getPermission());
    }
}
