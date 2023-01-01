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

namespace App\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\RateLimiterExceptionSubscriber
 */
final class RateLimiterExceptionSubscriberTest extends TestCase
{
    private RateLimiterExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new RateLimiterExceptionSubscriber();
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(RateLimiterExceptionSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onRateLimitExceededException
     */
    public function testRateLimitExceededException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        /** @var RateLimit $rateLimit */
        $rateLimit = $this->createMock(RateLimit::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RateLimitExceededException($rateLimit)
        );

        $this->subscriber->onRateLimitExceededException($event);

        $throwable = $event->getThrowable();

        self::assertInstanceOf(TooManyRequestsHttpException::class, $throwable);
        self::assertSame(429, $throwable->getStatusCode());
    }
}
