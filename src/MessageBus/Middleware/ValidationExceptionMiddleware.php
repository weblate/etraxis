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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Middleware to propagate validation exceptions further.
 */
final class ValidationExceptionMiddleware implements MiddlewareInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly LoggerInterface $logger, private readonly NormalizerInterface $normalizer)
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

            if ($throwable instanceof ValidationFailedException) {
                throw $throwable;
            }

            throw $exception;
        } catch (ValidationFailedException $exception) {
            $this->logger->debug('Validation exception', [
                'message'    => $exception->getMessage(),
                'violations' => $this->normalizer->normalize($exception->getViolations()),
            ]);

            throw $exception;
        }
    }
}
