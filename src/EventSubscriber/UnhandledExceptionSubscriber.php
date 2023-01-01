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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts any unhandled exception, thrown during an API request, into JSON response.
 */
class UnhandledExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly LoggerInterface $logger, protected readonly NormalizerInterface $normalizer)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Must be the last exception handler as the last resort to catch unhandled only exceptions.
            KernelEvents::EXCEPTION => ['onException', -2],
        ];
    }

    /**
     * In case of AJAX/JSON, logs the exception and converts it into JSON response with HTTP 500 error.
     */
    public function onException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $message  = $throwable->getMessage() ?: JsonResponse::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];
        $response = new JsonResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->logger->critical('Exception', ['error' => $message]);

        $event->setResponse($response);
    }
}
