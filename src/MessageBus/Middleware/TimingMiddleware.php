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
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * Middleware to calculate message processing time.
 */
final class TimingMiddleware implements MiddlewareInterface
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
        $start = microtime(true);

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            /** @var BusNameStamp $stamp */
            $stamp = $envelope->last(BusNameStamp::class);
            $stop  = microtime(true);

            $this->logger->debug('Message processing time', [
                'bus'   => $stamp->getBusName(),
                'time'  => $stop - $start,
                'class' => get_class($envelope->getMessage()),
            ]);
        }
    }
}
