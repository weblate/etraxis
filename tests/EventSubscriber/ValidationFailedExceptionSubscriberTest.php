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

namespace App\EventSubscriber;

use App\Serializer\Normalizer\ConstraintViolationsNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\ValidationFailedExceptionSubscriber
 */
final class ValidationFailedExceptionSubscriberTest extends TestCase
{
    private ValidationFailedExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $normalizer = new ConstraintViolationsNormalizer();

        $this->subscriber = new ValidationFailedExceptionSubscriber($normalizer);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(ValidationFailedExceptionSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onValidationFailedException
     */
    public function testValidationFailedException(): void
    {
        $expected = [
            [
                'property' => 'property',
                'value'    => '0',
                'message'  => 'This value should be "1" or more.',
            ],
        ];

        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/endpoint');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $command = new class() {
            /**
             * @Range(min="1", max="100")
             */
            public int $property = 0;
        };

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation(
            'This value should be "1" or more.',
            'This value should be {{ limit }} or more.',
            [
                '{{ value }}' => '"0"',
                '{{ limit }}' => '"1"',
            ],
            $command,
            'property',
            '0'
        ));

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new ValidationFailedException($command, $violations)
        );

        $this->subscriber->onValidationFailedException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame($expected, json_decode($content, true));
    }

    /**
     * @covers ::onValidationFailedException
     */
    public function testNotApiException(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/');

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $command = new class() {
            /**
             * @Range(min="1", max="100")
             */
            public int $property = 0;
        };

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation(
            'This value should be "1" or more.',
            'This value should be {{ limit }} or more.',
            [
                '{{ value }}' => '"0"',
                '{{ limit }}' => '"1"',
            ],
            $command,
            'property',
            '0'
        ));

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new ValidationFailedException($command, $violations)
        );

        $this->subscriber->onValidationFailedException($event);

        $response = $event->getResponse();

        self::assertNull($response);
    }
}
