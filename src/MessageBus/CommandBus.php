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

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Command bus.
 */
class CommandBus implements Contracts\CommandBusInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly MessageBusInterface $commandBus)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handle(object $command): void
    {
        $this->commandBus->dispatch($command);
    }
}
