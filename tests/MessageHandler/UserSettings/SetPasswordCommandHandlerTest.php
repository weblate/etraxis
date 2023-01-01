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

namespace App\MessageHandler\UserSettings;

use App\Entity\User;
use App\LoginTrait;
use App\Message\UserSettings\SetPasswordCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\MessageHandler\UserSettings\SetPasswordCommandHandler::__invoke
 */
final class SetPasswordCommandHandlerTest extends TransactionalTestCase
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

    public function testSuccessAsAdmin(): void
    {
        $this->loginUser('admin@example.com');

        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher $hasher */
        $hasher = self::getContainer()->get('security.password_hasher');

        $user = $this->repository->findOneByEmail('artem@example.com');

        self::assertTrue($hasher->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand($user->getId(), 'newone');

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($hasher->isPasswordValid($user, 'secret'));
        self::assertTrue($hasher->isPasswordValid($user, 'newone'));
    }

    public function testSuccessAsOwner(): void
    {
        $this->loginUser('artem@example.com');

        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher $hasher */
        $hasher = self::getContainer()->get('security.password_hasher');

        $user = $this->repository->findOneByEmail('artem@example.com');

        self::assertTrue($hasher->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand($user->getId(), 'newone');

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($hasher->isPasswordValid($user, 'secret'));
        self::assertTrue($hasher->isPasswordValid($user, 'newone'));
    }

    public function testValidationEmptyPassword(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('artem@example.com');

        $command = new SetPasswordCommand($user->getId(), '');

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set new password.');

        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('admin@example.com');

        $command = new SetPasswordCommand($user->getId(), 'secret');

        $this->commandBus->handle($command);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginUser('admin@example.com');

        $command = new SetPasswordCommand(self::UNKNOWN_ENTITY_ID, 'secret');

        $this->commandBus->handle($command);
    }

    public function testExternalUser(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to set new password.');

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');

        $command = new SetPasswordCommand($user->getId(), 'secret');

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('artem@example.com');

        $password = str_repeat('*', PasswordHasherInterface::MAX_PASSWORD_LENGTH + 1);
        $command  = new SetPasswordCommand($user->getId(), $password);

        $this->commandBus->handle($command);
    }
}
