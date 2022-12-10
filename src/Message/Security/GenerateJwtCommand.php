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

namespace App\Message\Security;

use App\MessageBus\Contracts\CommandInterface;

/**
 * Generates new JWT token for current user.
 */
final class GenerateJwtCommand implements CommandInterface
{
}
