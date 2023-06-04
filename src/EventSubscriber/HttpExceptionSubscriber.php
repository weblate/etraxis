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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts any HTTP exception, thrown during an API request, into JSON response.
 */
class HttpExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly NormalizerInterface $normalizer)
    {
    }

    /**
     * @see EventSubscriberInterface::getSubscribedEvents
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Must go behind other exception handlers as they can transform the current exception into HttpException.
            KernelEvents::EXCEPTION => ['onHttpException', -1],
        ];
    }

    /**
     * Converts HTTP error into corresponding JSON response.
     */
    public function onHttpException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof HttpException) {
            $message  = $throwable->getMessage() ?: JsonResponse::$statusTexts[$throwable->getStatusCode()];
            $response = new JsonResponse($message, $throwable->getStatusCode(), $throwable->getHeaders());

            $event->setResponse($response);
        }
    }
}
