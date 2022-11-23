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

namespace App\MessageBus\Contracts;

/**
 * Event bus interface.
 */
interface EventBusInterface
{
    /**
     * Sends the given event synchronously.
     *
     * NOTE. This function can be called from another message handler only.
     *
     * The event will be sent only:
     * - when the message handler is completed, and
     * - if it's completed with success.
     *
     * @param object $event The event or the event pre-wrapped in an envelope
     */
    public function send(object $event): void;

    /**
     * Sends the given event asynchronously.
     *
     * When sending from another message handler, the event will be sent immediately and
     * independently on the message handler success.
     *
     * @param object $event The event or the event pre-wrapped in an envelope
     */
    public function sendAsync(object $event): void;
}
