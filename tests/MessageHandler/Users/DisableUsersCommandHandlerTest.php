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

namespace App\MessageHandler\Users;

use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\DisableUsersCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\DisableUsersCommandHandler::__invoke
 */
final class DisableUsersCommandHandlerTest extends TransactionalTestCase
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

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        self::assertFalse($nhills->isDisabled());
        self::assertTrue($tberge->isDisabled());

        $command = new DisableUsersCommand([
            $nhills->getId(),
            $tberge->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertTrue($nhills->isDisabled());
        self::assertTrue($tberge->isDisabled());
    }

    public function testValidationGroupsCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new DisableUsersCommand([]);

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidGroups(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new DisableUsersCommand([
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
        $this->expectExceptionMessage('You are not allowed to disable one of these users.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('nhills@example.com');

        $command = new DisableUsersCommand([
            $user->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new DisableUsersCommand([
            self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testForbidden(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to disable one of these users.');

        $this->loginUser('admin@example.com');

        $admin = $this->repository->findOneByEmail('admin@example.com');

        $command = new DisableUsersCommand([
            $admin->getId(),
        ]);

        $this->commandBus->handle($command);
    }
}
