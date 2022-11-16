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

namespace App\MessageHandler\Users;

use App\Entity\Group;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\RemoveGroupsCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\RemoveGroupsCommandHandler::__invoke
 */
final class RemoveGroupsCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        $before = [
            'Company Staff',
            'Developers A',
            'Developers B',
        ];

        $after = [
            'Company Staff',
            'Developers B',
        ];

        $groupRepository = $this->doctrine->getRepository(Group::class);

        $devA = $groupRepository->findOneBy(['description' => 'Developers A']);
        $devC = $groupRepository->findOneBy(['description' => 'Developers C']);

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $groups = array_map(fn (Group $group) => $group->getDescription() ?? $group->getName(), $user->getGroups()->toArray());

        sort($groups);
        self::assertSame($before, $groups);

        $command = new RemoveGroupsCommand($user->getId(), [
            $devA->getId(),
            $devC->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        $groups = array_map(fn (Group $group) => $group->getDescription() ?? $group->getName(), $user->getGroups()->toArray());

        sort($groups);
        self::assertSame($after, $groups);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to manage this user.');

        $this->loginUser('artem@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $command = new RemoveGroupsCommand($user->getId(), [
            $devA->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);

        $command = new RemoveGroupsCommand(self::UNKNOWN_ENTITY_ID, [
            $devA->getId(),
        ]);

        $this->commandBus->handle($command);
    }
}
