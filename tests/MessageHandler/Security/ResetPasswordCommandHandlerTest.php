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

namespace App\MessageHandler\Security;

use App\Entity\User;
use App\Message\Security\ResetPasswordCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Security\ResetPasswordCommandHandler::__invoke
 */
final class ResetPasswordCommandHandlerTest extends TransactionalTestCase
{
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
        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher $hasher */
        $hasher = self::getContainer()->get('security.password_hasher');

        $user  = $this->repository->findOneByEmail('artem@example.com');
        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertTrue($hasher->isPasswordValid($user, 'secret'));
        self::assertTrue($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand($token, 'newone');

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($hasher->isPasswordValid($user, 'secret'));
        self::assertTrue($hasher->isPasswordValid($user, 'newone'));
        self::assertFalse($user->isResetTokenValid($token));
    }

    public function testUnknownToken(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $token   = str_replace('-', '', Uuid::v4()->toRfc4122());
        $command = new ResetPasswordCommand($token, 'secret');

        $this->commandBus->handle($command);
    }

    public function testExpiredToken(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $user = $this->repository->findOneByEmail('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT0M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertFalse($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand($token, 'secret');

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $user = $this->repository->findOneByEmail('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        $password = str_repeat('*', PasswordHasherInterface::MAX_PASSWORD_LENGTH + 1);
        $command  = new ResetPasswordCommand($token, $password);

        $this->commandBus->handle($command);
    }
}
