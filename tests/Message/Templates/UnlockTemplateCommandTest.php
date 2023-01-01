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

namespace App\Message\Templates;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Templates\UnlockTemplateCommand
 */
final class UnlockTemplateCommandTest extends TestCase
{
    /**
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $command = new UnlockTemplateCommand(1);

        self::assertSame(1, $command->getTemplate());
    }
}
