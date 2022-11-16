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

namespace App\MessageHandler\Groups;

use App\Entity\Group;
use App\Entity\User;
use App\LoginTrait;
use App\Message\Groups\RemoveMembersCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\GroupRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Groups\RemoveMembersCommandHandler::__invoke
 */
final class RemoveMembersCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private GroupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        $before = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'fdooley@example.com',
            'labshire@example.com',
        ];

        $after = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'labshire@example.com',
        ];

        /** @var \App\Repository\Contracts\UserRepositoryInterface $userRepository */
        $userRepository = $this->doctrine->getRepository(User::class);

        $fdooley = $userRepository->findOneByEmail('fdooley@example.com');
        $nhills  = $userRepository->findOneByEmail('nhills@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $members = array_map(fn (User $user) => $user->getEmail(), $group->getMembers()->toArray());

        sort($members);
        self::assertSame($before, $members);

        $command = new RemoveMembersCommand($group->getId(), [
            $fdooley->getId(),
            $nhills->getId(),
        ]);

        $this->commandBus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->getId());

        $members = array_map(fn (User $user) => $user->getEmail(), $group->getMembers()->toArray());

        sort($members);
        self::assertSame($after, $members);
    }

    public function testValidationUsersCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new RemoveMembersCommand($group->getId(), []);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This collection should contain 1 element or more.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidUsers(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new RemoveMembersCommand($group->getId(), [
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
        $this->expectExceptionMessage('You are not allowed to manage this group.');

        $this->loginUser('artem@example.com');

        /** @var User $fdooley */
        $fdooley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new RemoveMembersCommand($group->getId(), [
            $fdooley->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownGroup(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown group.');

        $this->loginUser('admin@example.com');

        /** @var User $fdooley */
        $fdooley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        $command = new RemoveMembersCommand(self::UNKNOWN_ENTITY_ID, [
            $fdooley->getId(),
        ]);

        $this->commandBus->handle($command);
    }
}
