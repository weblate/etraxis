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
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;

/**
 * Converts rate limiter exception, thrown during an API request, into corresponding HTTP exception.
 */
class RateLimiterExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onRateLimitExceededException',
        ];
    }

    /**
     * API rate limit exceeded.
     */
    public function onRateLimitExceededException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if (str_starts_with($request->getPathInfo(), '/api/') && $throwable instanceof RateLimitExceededException) {
            $event->setThrowable(new TooManyRequestsHttpException($throwable->getRetryAfter()->format('r')));
        }
    }
}
