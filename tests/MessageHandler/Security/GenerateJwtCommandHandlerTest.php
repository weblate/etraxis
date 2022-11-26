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
use App\Message\Security\GenerateJwtCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\TransactionalTestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Security\GenerateJwtCommandHandler::__invoke
 */
final class GenerateJwtCommandHandlerTest extends TransactionalTestCase
{
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
    }

    public function testSuccess(): void
    {
        $command = new GenerateJwtCommand('artem@example.com', 'secret');

        $token = $this->commandBus->handleWithResult($command);

        $payload = JWT::decode($token, new Key('$ecretf0rt3st', 'HS256'));

        self::assertSame('artem@example.com', $payload->sub ?? null);
        self::assertGreaterThan(time(), $payload->exp ?? null);
        self::assertLessThanOrEqual(time(), $payload->iat ?? null);
    }

    public function testValidationEmptyEmail(): void
    {
        $this->expectException(ValidationFailedException::class);

        $command = new GenerateJwtCommand('', 'secret');

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmailLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $command = new GenerateJwtCommand(str_pad('@example.com', User::MAX_EMAIL + 1, 'a', STR_PAD_LEFT), 'secret');

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 254 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationInvalidEmail(): void
    {
        $this->expectException(ValidationFailedException::class);

        $command = new GenerateJwtCommand('artem@example', 'secret');

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is not a valid email address.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationEmptyPassword(): void
    {
        $this->expectException(ValidationFailedException::class);

        $command = new GenerateJwtCommand('artem@example.com', '');

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $command = new GenerateJwtCommand('unknown@example.com', 'secret');

        $this->commandBus->handle($command);
    }

    public function testDisabledUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $command = new GenerateJwtCommand('tberge@example.com', 'secret');

        $this->commandBus->handle($command);
    }

    public function testExternalUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $command = new GenerateJwtCommand('einstein@ldap.forumsys.com', 'secret');

        $this->commandBus->handle($command);
    }

    public function testInvalidPassword(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $command = new GenerateJwtCommand('artem@example.com', 'wrong');

        $this->commandBus->handle($command);
    }
}
