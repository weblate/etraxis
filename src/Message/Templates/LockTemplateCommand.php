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

namespace App\Message\Templates;

use App\MessageBus\Contracts\CommandInterface;

/**
 * Locks specified template.
 */
final class LockTemplateCommand implements CommandInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(private readonly int $template)
    {
    }

    /**
     * @return int Template ID
     */
    public function getTemplate(): int
    {
        return $this->template;
    }
}
