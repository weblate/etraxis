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

//use Symfony\Component\Messenger\Envelope;

/**
 * Command bus interface.
 */
interface CommandBusInterface
{
    /**
     * Handles the given command.
     *
     * @param object $command The command or the command pre-wrapped in an envelope
     *
     * @see \Symfony\Component\Messenger\Envelope
     */
    public function handle(object $command): void;
}
