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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Middleware to propagate HTTP exceptions further.
 */
final class HttpExceptionMiddleware implements MiddlewareInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @see MiddlewareInterface::handle
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (HandlerFailedException $exception) {
            $throwable = $exception->getPrevious();

            if ($throwable instanceof HttpException) {
                throw $throwable;
            }

            throw $exception;
        } catch (HttpException $exception) {
            $this->logger->debug('HTTP exception', [
                'code'    => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
