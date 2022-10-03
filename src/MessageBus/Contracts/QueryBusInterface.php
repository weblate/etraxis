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
 * Query bus interface.
 */
interface QueryBusInterface
{
    /**
     * Executes the given query.
     *
     * @param object $query The query or the query pre-wrapped in an envelope
     *
     * @return mixed Query results
     *
     * @see \Symfony\Component\Messenger\Envelope
     */
    public function execute(object $query): mixed;
}
