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

namespace App\Security\Voter;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Issue;
use App\Entity\Template;
use App\Entity\User;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\Voter\PermissionsTrait
 */
final class PermissionsTraitTest extends TransactionalTestCase
{
    use PermissionsTrait;

    /**
     * @covers ::hasRolePermission
     */
    public function testHasRolePermission(): void
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertFalse($this->hasRolePermission($this->doctrine->getManager(), $template, SystemRoleEnum::Anyone, TemplatePermissionEnum::ViewIssues));

        self::assertTrue($this->hasRolePermission($this->doctrine->getManager(), $template, SystemRoleEnum::Author, TemplatePermissionEnum::EditIssues));
        self::assertFalse($this->hasRolePermission($this->doctrine->getManager(), $template, SystemRoleEnum::Author, TemplatePermissionEnum::DeleteIssues));

        self::assertTrue($this->hasRolePermission($this->doctrine->getManager(), $template, SystemRoleEnum::Responsible, TemplatePermissionEnum::AddComments));
        self::assertFalse($this->hasRolePermission($this->doctrine->getManager(), $template, SystemRoleEnum::Responsible, TemplatePermissionEnum::EditIssues));
    }

    /**
     * @covers ::hasGroupPermission
     */
    public function testHasGroupPermission(): void
    {
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $manager = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');
        $client  = $this->doctrine->getRepository(User::class)->findOneByEmail('clegros@example.com');

        self::assertTrue($this->hasGroupPermission($this->doctrine->getManager(), $issue->getTemplate(), $manager, TemplatePermissionEnum::AddComments));
        self::assertFalse($this->hasGroupPermission($this->doctrine->getManager(), $issue->getTemplate(), $client, TemplatePermissionEnum::AddComments));
    }

    /**
     * @covers ::hasPermission
     */
    public function testHasPermissionByAuthor(): void
    {
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $author      = $this->doctrine->getRepository(User::class)->findOneByEmail('clegros@example.com');
        $responsible = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');
        $support     = $this->doctrine->getRepository(User::class)->findOneByEmail('jkiehn@example.com');
        $client      = $this->doctrine->getRepository(User::class)->findOneByEmail('jmueller@example.com');

        self::assertTrue($this->hasPermission($this->doctrine->getManager(), $issue, $author, TemplatePermissionEnum::EditIssues));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $responsible, TemplatePermissionEnum::EditIssues));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $support, TemplatePermissionEnum::EditIssues));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $client, TemplatePermissionEnum::EditIssues));
    }

    /**
     * @covers ::hasPermission
     */
    public function testHasPermissionByResponsible(): void
    {
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $author      = $this->doctrine->getRepository(User::class)->findOneByEmail('clegros@example.com');
        $responsible = $this->doctrine->getRepository(User::class)->findOneByEmail('nhills@example.com');
        $support     = $this->doctrine->getRepository(User::class)->findOneByEmail('jkiehn@example.com');
        $client      = $this->doctrine->getRepository(User::class)->findOneByEmail('jmueller@example.com');

        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $author, TemplatePermissionEnum::ManageDependencies));
        self::assertTrue($this->hasPermission($this->doctrine->getManager(), $issue, $responsible, TemplatePermissionEnum::ManageDependencies));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $support, TemplatePermissionEnum::ManageDependencies));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $client, TemplatePermissionEnum::ManageDependencies));
    }

    /**
     * @covers ::hasPermission
     */
    public function testHasPermissionByGroup(): void
    {
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $support = $this->doctrine->getRepository(User::class)->findOneByEmail('jkiehn@example.com');
        $client  = $this->doctrine->getRepository(User::class)->findOneByEmail('jmueller@example.com');

        self::assertTrue($this->hasPermission($this->doctrine->getManager(), $issue, $support, TemplatePermissionEnum::PrivateComments));
        self::assertFalse($this->hasPermission($this->doctrine->getManager(), $issue, $client, TemplatePermissionEnum::PrivateComments));
    }
}
