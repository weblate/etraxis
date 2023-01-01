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

namespace App\MessageBus\Contracts;

/**
 * Command bus interface.
 */
interface CommandBusInterface
{
    /**
     * Handles the given command.
     *
     * @param object $command The command or the command pre-wrapped in an envelope
     */
    public function handle(object $command): void;

    /**
     * Handles the given command and returns its result.
     *
     * @param object $command The command or the command pre-wrapped in an envelope
     *
     * @return mixed The result of command execution
     */
    public function handleWithResult(object $command): mixed;
}
