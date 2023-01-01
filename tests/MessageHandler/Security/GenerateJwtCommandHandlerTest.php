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

namespace App\MessageHandler\Security;

use App\LoginTrait;
use App\Message\Security\GenerateJwtCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\TransactionalTestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Security\GenerateJwtCommandHandler::__invoke
 */
final class GenerateJwtCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('artem@example.com');

        $command = new GenerateJwtCommand();

        $token = $this->commandBus->handleWithResult($command);

        $payload = JWT::decode($token, new Key('$ecretf0rt3st', 'HS256'));

        self::assertSame('artem@example.com', $payload->sub ?? null);
        self::assertGreaterThan(time(), $payload->exp ?? null);
        self::assertLessThanOrEqual(time(), $payload->iat ?? null);
    }

    public function testUnknownUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $command = new GenerateJwtCommand();

        $this->commandBus->handle($command);
    }
}
