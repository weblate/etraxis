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

namespace App\MessageBus\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\Middleware\ValidationExceptionMiddleware
 */
final class ValidationExceptionMiddlewareTest extends TestCase
{
    /**
     * @covers ::handle
     */
    public function testNoException(): void
    {
        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $envelope->with(new BusNameStamp('test.bus'));
                    }
                };
            }
        };

        $logger     = $this->createMock(LoggerInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new ValidationExceptionMiddleware($logger, $normalizer);

        $envelope = $middleware->handle($envelope, $stack);

        self::assertNotNull($envelope->last(BusNameStamp::class));
    }

    /**
     * @covers ::handle
     */
    public function testValidationException(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Message of type "stdClass" failed validation.');

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        throw new ValidationFailedException($envelope->getMessage(), new ConstraintViolationList());
                    }
                };
            }
        };

        $logger     = $this->createMock(LoggerInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new ValidationExceptionMiddleware($logger, $normalizer);

        $middleware->handle($envelope, $stack);
    }

    /**
     * @covers ::handle
     */
    public function testHandlerFailedWithValidationException(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Message of type "stdClass" failed validation.');

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        $exception = new ValidationFailedException($envelope->getMessage(), new ConstraintViolationList());

                        throw new HandlerFailedException($envelope, [$exception]);
                    }
                };
            }
        };

        $logger     = $this->createMock(LoggerInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new ValidationExceptionMiddleware($logger, $normalizer);

        $middleware->handle($envelope, $stack);
    }

    /**
     * @covers ::handle
     */
    public function testHandlerFailedWithUnknownException(): void
    {
        $this->expectException(HandlerFailedException::class);
        $this->expectExceptionMessage('Something went wrong.');

        $stack = new class() implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class() extends StackMiddleware {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        $exception = new \LogicException('Something went wrong.');

                        throw new HandlerFailedException($envelope, [$exception]);
                    }
                };
            }
        };

        $logger     = $this->createMock(LoggerInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);

        $message    = new \stdClass();
        $envelope   = new Envelope($message);
        $middleware = new ValidationExceptionMiddleware($logger, $normalizer);

        $middleware->handle($envelope, $stack);
    }
}
