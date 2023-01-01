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

use App\Serializer\Normalizer\ConstraintViolationsNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\HttpExceptionSubscriber
 */
final class HttpExceptionSubscriberTest extends TestCase
{
    private HttpExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $normalizer = new ConstraintViolationsNormalizer();

        $this->subscriber = new HttpExceptionSubscriber($normalizer);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(HttpExceptionSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp400Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new BadRequestHttpException()
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Bad Request', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp401Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new UnauthorizedHttpException('')
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Unauthorized', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp403Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException()
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Forbidden', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp404Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new NotFoundHttpException()
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('Not Found', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp409Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new ConflictHttpException()
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame('Conflict', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttp429Exception(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new TooManyRequestsHttpException()
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        self::assertSame('Too Many Requests', trim($content, '"'));
    }

    /**
     * @covers ::onHttpException
     */
    public function testHttpCustomMessageException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException('You are not allowed for this action.')
        );

        $this->subscriber->onHttpException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('You are not allowed for this action.', trim($content, '"'));
    }
}
