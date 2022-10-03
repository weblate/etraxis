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

namespace App\MessageBus\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\Middleware\TimingMiddleware
 */
final class TimingMiddlewareTest extends TestCase
{
    /**
     * @covers ::handle
     */
    public function testHandle(): void
    {
        $logger = new class() extends AbstractLogger {
            private string $logs;

            public function __construct()
            {
                $this->logs = '';
            }

            public function log($level, $message, array $context = []): void
            {
                $this->logs .= $message;
            }

            public function contains($message): bool
            {
                return false !== mb_strpos($this->logs, $message);
            }
        };

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new StackMiddleware();
            }
        };

        $message  = new \stdClass();
        $stamp    = new BusNameStamp('test.bus');
        $envelope = new Envelope($message, [$stamp]);

        $middleware = new TimingMiddleware($logger);
        $middleware->handle($envelope, $stack);

        self::assertTrue($logger->contains('Message processing time'));
    }
}
