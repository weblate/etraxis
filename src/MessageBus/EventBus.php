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

namespace App\MessageBus;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Event bus.
 */
class EventBus implements Contracts\EventBusInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly MessageBusInterface $eventBus)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function send(object $event): void
    {
        $stamp   = new DispatchAfterCurrentBusStamp();
        $message = new Envelope($event, [$stamp]);

        $this->eventBus->dispatch($message);
    }

    /**
     * {@inheritDoc}
     */
    public function sendAsync(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
