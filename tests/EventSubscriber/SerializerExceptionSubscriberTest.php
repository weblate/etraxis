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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\SerializerExceptionSubscriber
 */
final class SerializerExceptionSubscriberTest extends TestCase
{
    private SerializerExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $this->subscriber = new SerializerExceptionSubscriber($translator);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(SerializerExceptionSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onNotEncodableValueException
     */
    public function testNotEncodableValueException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new NotEncodableValueException()
        );

        $this->subscriber->onNotEncodableValueException($event);

        $throwable = $event->getThrowable();

        self::assertInstanceOf(BadRequestHttpException::class, $throwable);
        self::assertSame(400, $throwable->getStatusCode());
        self::assertSame('The request body is not valid JSON.', $throwable->getMessage());
    }

    /**
     * @covers ::onMissingConstructorArgumentsException
     */
    public function testMissingConstructorArgumentsException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new MissingConstructorArgumentsException($request, 0, null, ['name'])
        );

        $this->subscriber->onMissingConstructorArgumentsException($event);

        $throwable = $event->getThrowable();

        self::assertInstanceOf(ValidationFailedException::class, $throwable);

        $violation = $throwable->getViolations()->get(0);

        self::assertSame('This value should not be blank.', $violation->getMessage());
        self::assertSame('name', $violation->getPropertyPath());
        self::assertNull($violation->getInvalidValue());
    }

    /**
     * @covers ::onNotNormalizableValueException
     */
    public function testNotNormalizableValueExceptionWithNull(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            NotNormalizableValueException::createForUnexpectedDataType('', null, ['string'], 'name')
        );

        $this->subscriber->onNotNormalizableValueException($event);

        $throwable = $event->getThrowable();

        self::assertInstanceOf(ValidationFailedException::class, $throwable);

        $violation = $throwable->getViolations()->get(0);

        self::assertSame('This value should not be blank.', $violation->getMessage());
        self::assertSame('name', $violation->getPropertyPath());
        self::assertSame('null', $violation->getInvalidValue());
    }

    /**
     * @covers ::onNotNormalizableValueException
     */
    public function testNotNormalizableValueExceptionWithInvalidType(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            NotNormalizableValueException::createForUnexpectedDataType('', 123, ['string'], 'name')
        );

        $this->subscriber->onNotNormalizableValueException($event);

        $throwable = $event->getThrowable();

        self::assertInstanceOf(ValidationFailedException::class, $throwable);

        $violation = $throwable->getViolations()->get(0);

        self::assertSame('This value is not valid.', $violation->getMessage());
        self::assertSame('name', $violation->getPropertyPath());
        self::assertSame('int', $violation->getInvalidValue());
    }
}
