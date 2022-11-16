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
use App\Message\Users\AddGroupsCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\AddGroupsCommandHandler::__invoke
 */
final class AddGroupsCommandHandlerTest extends TransactionalTestCase
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
            'Developers A',
            'Developers B',
            'Developers C',
        ];

        $groupRepository = $this->doctrine->getRepository(Group::class);

        $devB = $groupRepository->findOneBy(['description' => 'Developers B']);
        $devC = $groupRepository->findOneBy(['description' => 'Developers C']);

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $groups = array_map(fn (Group $group) => $group->getDescription() ?? $group->getName(), $user->getGroups()->toArray());

        sort($groups);
        self::assertSame($before, $groups);

        $command = new AddGroupsCommand($user->getId(), [
            $devB->getId(),
            $devC->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        $groups = array_map(fn (Group $group) => $group->getDescription() ?? $group->getName(), $user->getGroups()->toArray());

        sort($groups);
        self::assertSame($after, $groups);
    }

    public function testValidationGroupsCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $command = new AddGroupsCommand($user->getId(), []);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This collection should contain 1 element or more.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidGroups(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $command = new AddGroupsCommand($user->getId(), [
            'foo',
        ]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not valid.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to manage this user.');

        $this->loginUser('artem@example.com');

        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $command = new AddGroupsCommand($user->getId(), [
            $devC->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $command = new AddGroupsCommand(self::UNKNOWN_ENTITY_ID, [
            $devC->getId(),
        ]);

        $this->commandBus->handle($command);
    }
}
