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

use App\Entity\User;
use App\LoginTrait;
use App\Message\Users\EnableUsersCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Users\EnableUsersCommandHandler::__invoke
 */
final class EnableUsersCommandHandlerTest extends TransactionalTestCase
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

        $command = new EnableUsersCommand([
            $nhills->getId(),
            $tberge->getId(),
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertFalse($nhills->isDisabled());
        self::assertFalse($tberge->isDisabled());
    }

    public function testValidationGroupsCount(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $command = new EnableUsersCommand([]);

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

        $command = new EnableUsersCommand([
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
        $this->expectExceptionMessage('You are not allowed to enable one of these users.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('tberge@example.com');

        $command = new EnableUsersCommand([
            $user->getId(),
        ]);

        $this->commandBus->handle($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new EnableUsersCommand([
            self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }
}
