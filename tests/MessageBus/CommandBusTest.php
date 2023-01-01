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

namespace App\MessageBus;

use App\MessageBus\Contracts\CommandBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\CommandBus
 */
final class CommandBusTest extends TestCase
{
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $messageBus = new class() implements MessageBusInterface {
            public function dispatch($message, array $stamps = []): Envelope
            {
                $envelope = new Envelope($message);

                /** @var callable $callable */
                $callable = $envelope->getMessage();
                $result   = $callable($message);

                return $envelope
                    ->with(new BusNameStamp('command.bus'))
                    ->with(new HandledStamp($result, 'test_handler'))
                ;
            }
        };

        $this->commandBus = new CommandBus($messageBus);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void
    {
        $command = new class() {
            public function __invoke(): void
            {
                $this->called = true;
            }

            public bool $called = false;
        };

        self::assertFalse($command->called);

        $this->commandBus->handle($command);

        self::assertTrue($command->called);
    }

    /**
     * @covers ::handleWithResult
     */
    public function testHandleWithResult(): void
    {
        $command = new class() {
            public function __invoke(): string
            {
                $this->called = true;

                return 'Test';
            }

            public bool $called = false;
        };

        self::assertFalse($command->called);

        $result = $this->commandBus->handleWithResult($command);

        self::assertTrue($command->called);
        self::assertSame('Test', $result);
    }
}
