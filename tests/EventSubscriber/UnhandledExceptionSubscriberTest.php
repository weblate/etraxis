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
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\UnhandledExceptionSubscriber
 */
final class UnhandledExceptionSubscriberTest extends TestCase
{
    private UnhandledExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $logger     = new NullLogger();
        $normalizer = new ConstraintViolationsNormalizer();

        $this->subscriber = new UnhandledExceptionSubscriber($logger, $normalizer);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(UnhandledExceptionSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onException
     */
    public function testException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \Exception('Something went wrong.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Something went wrong.', trim($content, '"'));
    }
}
