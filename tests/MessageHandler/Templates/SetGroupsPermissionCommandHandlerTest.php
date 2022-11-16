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

namespace App\MessageHandler\Templates;

use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Group;
use App\Entity\Template;
use App\Entity\TemplateGroupPermission;
use App\LoginTrait;
use App\Message\Templates\SetGroupsPermissionCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Templates\SetGroupsPermissionCommandHandler::__invoke
 */
final class SetGroupsPermissionCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface         $commandBus;
    private TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        $before = [
            TemplatePermissionEnum::CreateIssues,
            TemplatePermissionEnum::ViewIssues,
        ];

        $after = [
            TemplatePermissionEnum::DeleteFiles,
            TemplatePermissionEnum::CreateIssues,
            TemplatePermissionEnum::ViewIssues,
        ];

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertSame($before, $this->permissionsToArray($template->getGroupPermissions(), $group->getId()));

        $command = new SetGroupsPermissionCommand($template->getId(), TemplatePermissionEnum::DeleteFiles, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);

        /** Group $group2 */
        [$group2] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand($template->getId(), TemplatePermissionEnum::PrivateComments, [
            $group2->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertSame($after, $this->permissionsToArray($template->getGroupPermissions(), $group->getId()));
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set template permissions.');

        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand($template->getId(), TemplatePermissionEnum::DeleteFiles, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand(self::UNKNOWN_ENTITY_ID, TemplatePermissionEnum::DeleteFiles, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup(): void
    {
        $this->expectException(HandlerFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        $command = new SetGroupsPermissionCommand($template->getId(), TemplatePermissionEnum::DeleteFiles, [
            $group->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    private function permissionsToArray(Collection $permissions, int $groupId): array
    {
        $filtered = array_filter($permissions->toArray(), fn (TemplateGroupPermission $permission) => $permission->getGroup()->getId() === $groupId);
        $result   = array_map(fn (TemplateGroupPermission $permission) => $permission->getPermission(), $filtered);

        usort($result, fn (TemplatePermissionEnum $p1, TemplatePermissionEnum $p2) => strcmp($p1->value, $p2->value));

        return $result;
    }
}
