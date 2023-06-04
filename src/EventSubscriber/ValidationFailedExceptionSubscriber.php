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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts any validation exception, thrown during an API request, into JSON response.
 */
class ValidationFailedExceptionSubscriber implements EventSubscriberInterface
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
            // Must go behind other exception handlers as they can transform the current exception into ValidationFailedException.
            KernelEvents::EXCEPTION => ['onValidationFailedException', -1],
        ];
    }

    /**
     * The request has failed the validation.
     */
    public function onValidationFailedException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if (str_starts_with($request->getPathInfo(), '/api/') && $throwable instanceof ValidationFailedException) {
            $violations = $this->normalizer->normalize($throwable->getViolations(), 'json');
            $response   = new JsonResponse($violations, Response::HTTP_BAD_REQUEST);

            $event->setResponse($response);
        }
    }
}
